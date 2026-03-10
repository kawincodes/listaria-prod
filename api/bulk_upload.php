<?php
require_once __DIR__ . '/../includes/session.php';
require '../includes/db.php';
require '../includes/SimpleSMTP.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bulk_file'])) {
    $file = $_FILES['bulk_file'];
    $user_id = $_SESSION['user_id'];
    if (($_SESSION['account_type'] ?? '') === 'admin' && !empty($_POST['vendor_id'])) {
        $user_id = (int)$_POST['vendor_id'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Upload failed with error code " . $file['error']);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($extension !== 'csv') {
        die("Only CSV files are supported for now. Please use the template.");
    }

    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        $header = fgetcsv($handle, 0, ",", '"', ''); // Skip header, 0 = no limit
        
        // Process uploaded images and map original names (case-insensitive) to new paths
        $imageMap = [];
        if (isset($_FILES['bulk_images']) && is_array($_FILES['bulk_images']['name'])) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $fileCount = count($_FILES['bulk_images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['bulk_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['bulk_images']['tmp_name'][$i];
                    $original_name = basename($_FILES['bulk_images']['name'][$i]);
                    $new_name = uniqid() . '_' . $original_name;
                    $target_file = $upload_dir . $new_name;
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        // Store in lowercase for case-insensitive lookup
                        $imageMap[strtolower($original_name)] = 'uploads/' . $new_name;
                    }
                }
            }
        }
        
        error_log("Global Image Map: " . print_r($imageMap, true));

        $successCount = 0;
        $failCount = 0;

        while (($data = fgetcsv($handle, 0, ",", '"', '')) !== FALSE) {
            // Mapping: Title, Brand, Category, Condition, Location, Description, Price, Image URLs
            if (count($data) < 7) continue;

            $title = trim($data[0] ?? '');
            $brand = trim($data[1] ?? '');
            
            // Normalize category
            $category = trim($data[2] ?? '');
            $cat_lower = strtolower($category);
            
            $valid_categories = ['Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories', 'Phones', 'Laptops', 'Fashion', 'Books', 'Home', 'Gaming', 'Sports', 'Kids', 'Others'];
            $normalized_cat = '';
            
            if (in_array($cat_lower, ['top', 'tops'])) $normalized_cat = 'Tops';
            elseif (in_array($cat_lower, ['bottom', 'bottoms'])) $normalized_cat = 'Bottoms';
            elseif (in_array($cat_lower, ['jacket', 'jackets'])) $normalized_cat = 'Jackets';
            elseif (in_array($cat_lower, ['shoe', 'shoes'])) $normalized_cat = 'Shoes';
            elseif (in_array($cat_lower, ['bag', 'bags'])) $normalized_cat = 'Bags';
            elseif (in_array($cat_lower, ['accessory', 'accessories'])) $normalized_cat = 'Accessories';
            else {
                // Check against predefined list
                $uc_cat = ucfirst(strtolower($category));
                if (in_array($uc_cat, $valid_categories)) {
                     $normalized_cat = $uc_cat;
                } else {
                     $normalized_cat = 'Others'; // Default fallback mapped to predefined
                }
            }
            $category = $normalized_cat;

            $condition = trim($data[3] ?? '');
            $location = trim($data[4] ?? '');
            $description = trim($data[5] ?? '');
            $price = (float)preg_replace('/[^0-9.]/', '', $data[6] ?? '0');
            $price_min = $price;
            $price_max = $price; // Set both to same value
            
            // Support multiple delimiters: comma, semicolon, or pipe
            $image_raw = isset($data[7]) ? trim($data[7]) : '';
            // Split ONLY by comma, semicolon, or pipe to preserve spaces in filenames
            $image_names = preg_split('/[,\;|]/', $image_raw, -1, PREG_SPLIT_NO_EMPTY);
            
            // Map original filenames to the new uploaded paths
            $mapped_paths = [];
            foreach ($image_names as $img_name) {
                // Trim any extra whitespace or quotes around the filename
                $img_name = trim($img_name, " \t\n\r\0\x0B\"'");
                if (empty($img_name)) continue;

                // Extract just the filename, handling any Windows or Unix absolute paths
                $img_name_clean = strtolower(basename(str_replace('\\', '/', $img_name)));
                if (isset($imageMap[$img_name_clean])) {
                    $mapped_paths[] = $imageMap[$img_name_clean];
                } elseif (filter_var($img_name, FILTER_VALIDATE_URL)) {
                    // Fallback to allow direct URLs if provided
                    $mapped_paths[] = trim($img_name);
                }
            }

            $json_images = json_encode($mapped_paths);

            // Determine if listing should be auto-approved (e.g., if user is admin)
            $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
            $status = $is_admin ? 'approved' : 'pending';

            $stmt = $pdo->prepare("INSERT INTO products (title, brand, category, location, condition_tag, description, price_min, price_max, image_paths, user_id, approval_status, is_published) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

            try {
                if ($stmt->execute([$title, $brand, $category, $location, $condition, $description, $price_min, $price_max, $json_images, $user_id, $status])) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            } catch (Exception $e) {
                error_log("Bulk Upload Error on {$title}: " . $e->getMessage());
                // Ignore the row and increment fail count
                $failCount++;
            }
        }
        fclose($handle);
        
        // Send Summary Email Notification
        try {
            $uStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $uStmt->execute([$_SESSION['user_id']]);
            $user = $uStmt->fetch();

            if ($user && $successCount > 0) {
                $smtp = createSmtp($pdo);

                $subject = "Bulk Upload Summary: " . $successCount . " Products Listed";
                $body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <h2 style='color: #6B21A8;'>Hello " . htmlspecialchars($user['full_name']) . "!</h2>
                        <p>Your bulk product upload has been processed successfully.</p>
                        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                        <ul style='list-style: none; padding: 0;'>
                            <li><strong>Successfully Listed:</strong> " . $successCount . " products</li>
                            <li><strong>Failed:</strong> " . $failCount . " products</li>
                        </ul>
                        <p style='margin-top: 20px;'>The successfully listed products are now <strong>pending approval</strong> from our team.</p>
                        <p>Thank you for choosing Listaria.</p>
                        <br>
                        <p style='font-size: 0.8rem; color: #999;'>This is an automated email, please do not reply.</p>
                    </div>
                ";

                $smtp->send($user['email'], $subject, $body, 'Listaria Support');
            }
        } catch (Exception $e) {
            error_log("Failed to send bulk upload summary email: " . $e->getMessage());
        }

        header("Location: ../profile.php?tab=listings&msg=bulk_complete&success=$successCount&fail=$failCount");
    } else {
        die("Could not open file.");
    }
} else {
    header("Location: ../sell.php");
}
