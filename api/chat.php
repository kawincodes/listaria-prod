<?php
// Ensure NO HTML errors break the JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Global try-catch to catch connection/permission errors
try {
    require '../includes/db.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    // --- Helper: Privacy Filter ---
    function filterMessage($text) {
        $text = preg_replace('/\b\d{10}\b/', '[HIDDEN PHONE]', $text);
        $text = preg_replace('/\blocation\b/i', '[HIDDEN]', $text);
        return $text;
    }

    if ($action === 'start_negotiation') {
        $product_id = $_POST['product_id'];
        $seller_id = $_POST['seller_id'];
        
        $stmt = $pdo->prepare("SELECT id FROM negotiations WHERE product_id = ? AND buyer_id = ?");
        $stmt->execute([$product_id, $user_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            echo json_encode(['success' => true, 'negotiation_id' => $existing['id']]);
        } else {
            // This is where it crashes if PERMISSIONS are bad
            $stmt = $pdo->prepare("INSERT INTO negotiations (product_id, buyer_id, seller_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$product_id, $user_id, $seller_id])) {
                echo json_encode(['success' => true, 'negotiation_id' => $pdo->lastInsertId()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to start negotiation']);
            }
        }
    } 
    elseif ($action === 'send_message') {
        $negotiation_id = $_POST['negotiation_id'];
        $message = filterMessage($_POST['message']); // Apply Filter
        
        $stmt = $pdo->prepare("INSERT INTO messages (negotiation_id, sender_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$negotiation_id, $user_id, $message])) {
            
            // Mark the negotiation as unread for the OTHER party
            // We assume that if the sender is sending, they have read it.
            // The logic in profile.php counts is_read=0.
            // So we need to set is_read=0 when a message is sent.
            // Ideally, we should check who the receiver is, but for simplicity in this schema:
            // Let's assume is_read=0 means "Seller has unread messages" if the sender is Buyer?
            // Or "User has unread messages"?
            // The profile.php logic: if(isset($o['is_read']) && $o['is_read'] == 0) $unread_offers++;
            // $my_offers fetches negotiations where seller_id = $user_id.
            // So IF the BUYER sends a message, is_read should become 0.
            
            // Let's check sender vs seller.
            $stmt = $pdo->prepare("SELECT seller_id, buyer_id FROM negotiations WHERE id = ?");
            $stmt->execute([$negotiation_id]);
            $neg = $stmt->fetch();
            
            if ($neg) {
                // If Sender is Buyer -> Notify Seller (is_read = 0)
                if ($user_id == $neg['buyer_id']) {
                    try {
                        $pdo->prepare("UPDATE negotiations SET is_read = 0 WHERE id = ?")->execute([$negotiation_id]);
                    } catch (PDOException $e) {
                        // Self-Heal: Add is_read if missing
                        if (strpos($e->getMessage(), 'no such column') !== false) {
                            $pdo->exec("ALTER TABLE negotiations ADD COLUMN is_read INTEGER DEFAULT 1");
                            $pdo->prepare("UPDATE negotiations SET is_read = 0 WHERE id = ?")->execute([$negotiation_id]);
                        }
                    }
                }
                // If Sender is Seller -> Notify Buyer (is_buyer_read = 0)
                elseif ($user_id == $neg['seller_id']) {
                    try {
                        $pdo->prepare("UPDATE negotiations SET is_buyer_read = 0 WHERE id = ?")->execute([$negotiation_id]);
                    } catch (PDOException $e) {
                         // Self-Heal: Add is_buyer_read if missing
                         if (strpos($e->getMessage(), 'no such column') !== false) {
                            $pdo->exec("ALTER TABLE negotiations ADD COLUMN is_buyer_read INTEGER DEFAULT 1");
                            $pdo->prepare("UPDATE negotiations SET is_buyer_read = 0 WHERE id = ?")->execute([$negotiation_id]);
                        }
                    }
                }
            }

            echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to send']);
        }
    }
    elseif ($action === 'get_messages') {
        $negotiation_id = $_POST['negotiation_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE negotiation_id = ? ORDER BY created_at ASC");
        $stmt->execute([$negotiation_id]);
        $messages = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT final_price, status, buyer_id, seller_id FROM negotiations WHERE id = ?");
        $stmt->execute([$negotiation_id]);
        $neg = $stmt->fetch();
        
        // Mark as Read Logic
        if ($neg) {
            if ($user_id == $neg['seller_id']) {
                $pdo->prepare("UPDATE negotiations SET is_read = 1 WHERE id = ?")->execute([$negotiation_id]);
            } elseif ($user_id == $neg['buyer_id']) {
                // Check if column exists first logic could be heavy.
                // Just try update, catch error if column missing (unlikely if flow followed, but safe).
                try {
                    $pdo->prepare("UPDATE negotiations SET is_buyer_read = 1 WHERE id = ?")->execute([$negotiation_id]);
                } catch (Exception $e) {}
            }
        }

        echo json_encode([
            'success' => true, 
            'messages' => $messages,
            'current_user_id' => $user_id,
            'final_price' => $neg['final_price'] ?? null
        ]);
    }
    elseif ($action === 'set_final_price') {
        $negotiation_id = $_POST['negotiation_id'];
        $price = $_POST['price'];
        
        $stmt = $pdo->prepare("SELECT seller_id FROM negotiations WHERE id = ?");
        $stmt->execute([$negotiation_id]);
        $neg = $stmt->fetch();
        
        if ($neg && $neg['seller_id'] == $user_id) {
            $stmt = $pdo->prepare("UPDATE negotiations SET final_price = ? WHERE id = ?");
            $stmt->execute([$price, $negotiation_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Only seller can set price']);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    // Catch ANY crash (Permission denied, DB locked, etc) and return as JSON
    // Include the DB Path to debug "No Such Table" errors
    $db_debug = isset($db_file) ? " (DB: $db_file)" : " (DB: Unknown)";
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage() . $db_debug]);
}
?>
