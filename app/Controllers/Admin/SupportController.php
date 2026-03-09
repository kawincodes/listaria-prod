<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SupportTicketModel;
use App\Models\UserModel;

class SupportController extends BaseController
{
    public function index()
    {
        $ticketModel = new SupportTicketModel();
        $userModel = new UserModel();

        $filter = $this->request->getGet('filter');
        $builder = $ticketModel->orderBy('created_at', 'DESC');

        if ($filter && $filter !== 'all') {
            $builder->where('status', $filter);
        }

        $tickets = $builder->findAll();

        foreach ($tickets as &$ticket) {
            if ($ticket['user_id']) {
                $ticket['user'] = $userModel->find($ticket['user_id']);
            }
        }

        return view('admin/support', [
            'activePage' => 'support',
            'tickets' => $tickets,
            'filter' => $filter,
        ]);
    }

    public function reply()
    {
        $ticketModel = new SupportTicketModel();
        $ticketId = $this->request->getPost('ticket_id');
        $reply = $this->request->getPost('admin_reply');

        $ticketModel->update($ticketId, [
            'admin_reply' => $reply,
            'status' => 'replied',
        ]);

        return redirect()->to('/admin/support')->with('success', 'Reply sent.');
    }

    public function update()
    {
        $ticketModel = new SupportTicketModel();
        $ticketId = $this->request->getPost('ticket_id');
        $status = $this->request->getPost('status');

        $ticketModel->update($ticketId, ['status' => $status]);
        return redirect()->to('/admin/support')->with('success', 'Ticket updated.');
    }
}
