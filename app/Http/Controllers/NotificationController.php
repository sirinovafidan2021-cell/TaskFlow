<?php
namespace App\Http\Controllers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
class NotificationController { public function index(Request $request): View { return view('notifications.index', ['notifications' => $request->user()->notifications()->latest()->paginate(20)]); } public function read(Request $request, DatabaseNotification $notification): RedirectResponse { abort_unless($notification->notifiable_id === $request->user()->id && $notification->notifiable_type === $request->user()::class, 404); $notification->markAsRead(); return back(); } public function readAll(Request $request): RedirectResponse { $request->user()->unreadNotifications->markAsRead(); return back(); } }
