<?php

namespace App\Http\Controllers;

use App\Models\WahaSender;
use App\Services\WahaService;
use Illuminate\Http\Request;

class WahaController extends Controller
{
    protected $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:waha_senders,id',
            'recipient' => 'required|string',
            'message' => 'required|string',
        ]);

        $sender = WahaSender::find($request->sender_id);

        $response = $this->wahaService->sendMessage($sender, $request->recipient, $request->message);

        return response()->json($response);
    }

    public function sendBulkMessages(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:waha_senders,id',
            'recipients' => 'required|array',
            'message' => 'required|string',
        ]);

        $sender = WahaSender::find($request->sender_id);
        $responses = [];

        foreach ($request->recipients as $recipient) {
            $message = str_replace(['{{name}}', '{{nama_pelanggan}}'], $recipient['name'], $request->message);
            $responses[] = $this->wahaService->sendMessage($sender, $recipient['phone'], $message);
        }

        return response()->json($responses);
    }
}
