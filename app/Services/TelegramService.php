<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    protected string $token;
    protected string $chatId;
    protected ?int   $topicId = null;

    protected ?int $topicAttendance;
    protected ?int $topicLeave;
    protected ?int $topicDevice;

    public function __construct()
    {
        $this->token           = config('services.telegram.bot_token', '');
        $this->chatId          = config('services.telegram.chat_id', '');
        $this->topicAttendance = (int) config('services.telegram.topic_attendance') ?: null;
        $this->topicLeave      = (int) config('services.telegram.topic_leave') ?: null;
        $this->topicDevice     = (int) config('services.telegram.topic_device') ?: null;
    }

    // Override config with per-company Telegram settings
    public function forCompany(?Company $company): static
    {
        if (!$company) return $this;

        if ($company->telegram_bot_token) $this->token  = $company->telegram_bot_token;
        if ($company->telegram_chat_id)   $this->chatId = $company->telegram_chat_id;

        if ($company->telegram_topic_attendance) $this->topicAttendance = (int) $company->telegram_topic_attendance;
        if ($company->telegram_topic_leave)      $this->topicLeave      = (int) $company->telegram_topic_leave;
        if ($company->telegram_topic_device)     $this->topicDevice     = (int) $company->telegram_topic_device;

        return $this;
    }

    // Fluent topic setter — resets after each send
    public function topic(int|string|null $topicId): static
    {
        $this->topicId = $topicId ? (int) $topicId : null;
        return $this;
    }

    public function attendance(): static { return $this->topic($this->topicAttendance); }
    public function leave(): static      { return $this->topic($this->topicLeave); }
    public function device(): static     { return $this->topic($this->topicDevice); }

    public function send(string $text): bool
    {
        return $this->sendRaw($text, 'HTML');
    }

    public function sendMarkdown(string $text): bool
    {
        return $this->sendRaw($text, 'Markdown');
    }

    protected function sendRaw(string $text, string $parseMode): bool
    {
        if (empty($this->token) || empty($this->chatId)) return false;

        $topicId       = $this->topicId;
        $this->topicId = null; // reset after send

        try {
            $payload = [
                'chat_id'    => $this->chatId,
                'text'       => $text,
                'parse_mode' => $parseMode,
            ];

            if ($topicId) {
                $payload['message_thread_id'] = $topicId;
            }

            $res = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", $payload);
            return $res->successful() && $res->json('ok');
        } catch (\Exception) {
            return false;
        }
    }

    // ── Attendance ────────────────────────────────────────────────

    public function notifyCheckIn(
        string $empName, string $empId, string $dept,
        string $locationName, string $address,
        float $distance, string $time,
        ?string $status, string $workStart
    ): bool {
        $statusLine = match ($status) {
            'on_time' => '✅ Status: <b>On Time</b>',
            'late'    => '⏰ Status: <b>Late</b>',
            default   => '',
        };

        return $this->attendance()->send(
            "✅ <b>CHECK IN</b>\n"
            . "👤 <b>{$empName}</b> ({$empId})\n"
            . "🏢 {$dept}\n"
            . "📍 <b>{$locationName}</b>\n"
            . "🗺 {$address}\n"
            . "📏 {$distance}m from center\n"
            . "🕐 {$time}\n"
            . "⏱ Work start: {$workStart}\n"
            . $statusLine
        );
    }

    public function notifyCheckOut(
        string $empName, string $empId, string $dept,
        string $locationName, string $address,
        float $distance, string $time,
        ?string $status, string $ot, string $workEnd
    ): bool {
        $statusLine = match ($status) {
            'on_time' => '✅ Status: <b>On Time</b>',
            'early'   => '⚡ Status: <b>Left Early</b>',
            default   => '',
        };

        $otLine = ($ot !== '—') ? "\n⏳ OT: <b>{$ot}</b>" : '';

        return $this->attendance()->send(
            "🔴 <b>CHECK OUT</b>\n"
            . "👤 <b>{$empName}</b> ({$empId})\n"
            . "🏢 {$dept}\n"
            . "📍 <b>{$locationName}</b>\n"
            . "🗺 {$address}\n"
            . "📏 {$distance}m from center\n"
            . "🕐 {$time}\n"
            . "⏱ Work end: {$workEnd}\n"
            . $statusLine
            . $otLine
        );
    }

    public function notifyOutOfRange(
        string $empName, string $empId,
        string $locationName, float $distance, int $radius, string $time
    ): bool {
        return $this->attendance()->send(
            "⚠️ <b>OUT OF RANGE — REJECTED</b>\n"
            . "👤 <b>{$empName}</b> ({$empId})\n"
            . "📍 Tried to scan at: <b>{$locationName}</b>\n"
            . "📏 Distance: {$distance}m (allowed: {$radius}m)\n"
            . "🕐 {$time}"
        );
    }

    // ── Device ────────────────────────────────────────────────────

    public function notifyDeviceRequest(string $empName, string $empId, string $deviceName): bool
    {
        return $this->device()->send(
            "📱 <b>DEVICE CHANGE REQUEST</b>\n"
            . "👤 <b>{$empName}</b> ({$empId})\n"
            . "📲 Device: {$deviceName}\n"
            . "🕐 " . now()->format('H:i · D d M Y') . "\n\n"
            . "⚙️ Approve at: " . url('/devices/requests')
        );
    }

    public function notifyDeviceApproved(string $empName, string $empId, string $deviceName): bool
    {
        return $this->device()->send(
            "✅ <b>DEVICE APPROVED</b>\n"
            . "👤 <b>{$empName}</b> ({$empId})\n"
            . "📲 Device: {$deviceName}\n"
            . "🕐 " . now()->format('H:i · D d M Y') . "\n"
            . "You can now use your new device to check in."
        );
    }

    public function notifyDeviceRejected(string $empName, string $empId, string $deviceName, string $reason): bool
    {
        return $this->device()->send(
            "❌ <b>DEVICE REJECTED</b>\n"
            . "👤 <b>{$empName}</b> ({$empId})\n"
            . "📲 Device: {$deviceName}\n"
            . "📝 Reason: {$reason}\n"
            . "🕐 " . now()->format('H:i · D d M Y')
        );
    }

    // ── Leave ─────────────────────────────────────────────────────

    public function notifyLeaveSubmitted(string $empName, string $empId, string $typeLabel, string $period, string $reason): bool
    {
        return $this->leave()->sendMarkdown(
            "🔔 *New Leave Request*\n\n"
            . "👤 *{$empName}* ({$empId})\n"
            . "📌 {$typeLabel}\n"
            . "📆 {$period}\n"
            . "💬 " . ($reason ?: '—')
        );
    }

    public function notifyLeaveApproved(string $empName, string $empId, string $typeLabel, string $period, ?string $note): bool
    {
        $text = "📋 *Leave Request Updated*\n\n"
            . "👤 *{$empName}* ({$empId})\n"
            . "📌 {$typeLabel}\n"
            . "📆 {$period}\n"
            . "✅ *Approved*";
        if ($note) $text .= "\n💬 " . $note;
        return $this->leave()->sendMarkdown($text);
    }

    public function notifyLeaveRejected(string $empName, string $empId, string $typeLabel, string $period, ?string $note): bool
    {
        $text = "📋 *Leave Request Updated*\n\n"
            . "👤 *{$empName}* ({$empId})\n"
            . "📌 {$typeLabel}\n"
            . "📆 {$period}\n"
            . "❌ *Rejected*";
        if ($note) $text .= "\n💬 " . $note;
        return $this->leave()->sendMarkdown($text);
    }
}
