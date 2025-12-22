<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $conversations = auth()->user()->conversations()
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->latest('updated_at')
            ->paginate(20);

        return view('messages.index', compact('conversations'));
    }

    public function show(Conversation $conversation)
    {
        // Ensure user is part of conversation
        if ($conversation->user_one_id !== auth()->id() && $conversation->user_two_id !== auth()->id()) {
            abort(403);
        }

        $conversation->markAsRead(auth()->user());

        $messages = $conversation->messages()
            ->with('sender')
            ->latest()
            ->paginate(50);

        $otherUser = $conversation->getOtherUser(auth()->user());

        return view('messages.show', compact('conversation', 'messages', 'otherUser'));
    }

    public function create(string $username)
    {
        $user = User::where('username', $username)->firstOrFail();

        if ($user->id === auth()->id()) {
            return redirect()->route('messages.index');
        }

        $conversation = Conversation::findOrCreateBetween(auth()->user(), $user);

        return redirect()->route('messages.show', $conversation);
    }

    public function store(Request $request, Conversation $conversation)
    {
        // Ensure user is part of conversation
        if ($conversation->user_one_id !== auth()->id() && $conversation->user_two_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $request->body,
        ]);

        $conversation->touch();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message->load('sender'),
            ]);
        }

        return back();
    }
}
