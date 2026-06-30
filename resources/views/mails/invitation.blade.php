<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Invited</title>
</head>

<body style="margin:0;padding:0;background:#F0EFEB;font-family:'Segoe UI',Arial,sans-serif;">

    <div style="max-width:520px;margin:40px auto;padding:0 16px 40px;">

        {{-- Card --}}
        <div style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.10);">

            {{-- Accent bar --}}
            <div style="height:5px;background:linear-gradient(90deg,#6366f1,#7c3aed,#9333ea);"></div>

            {{-- Header --}}
            <div style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:36px 36px 28px;">
                <p
                    style="margin:0 0 8px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:#a5b4fc;">
                    Invitation
                </p>
                <h1 style="margin:0 0 10px;font-size:26px;font-weight:700;color:#ffffff;line-height:1.2;">
                    You've been invited!
                </h1>
                <div
                    style="display:inline-block;background:rgba(255,255,255,0.15);border-radius:20px;padding:5px 14px;">
                    <p style="margin:0;font-size:13px;color:#e0e7ff;">
                        Invited by <strong style="color:#ffffff;">{{ $invitation->inviter->name }}</strong>
                    </p>
                </div>
            </div>

            {{-- Body --}}
            <div style="padding:32px 36px;">

                {{-- Main message --}}
                <p style="margin:0 0 8px;font-size:14px;color:#6B7280;">You have been invited to join the</p>
                <div
                    style="background:#F5F3FF;border-radius:12px;padding:14px 18px;margin-bottom:24px;border-left:4px solid #7c3aed;">
                    <p
                        style="margin:0;font-size:11px;font-weight:600;color:#7c3aed;text-transform:uppercase;letter-spacing:0.06em;">
                        {{ ucfirst($invitation->type) }}
                    </p>
                    <p style="margin:4px 0 0;font-size:16px;font-weight:700;color:#1F2937;">
                        {{ $invitableName }}
                    </p>
                </div>

                {{-- Expiry Alert --}}
                @php
                    $daysLeft = (int) ceil(now()->floatDiffInDays($invitation->expires_at));
                    $hoursLeft = (int) ceil(now()->floatDiffInHours($invitation->expires_at));
                @endphp

                @if ($hoursLeft < 24)
                    <div
                        style="background:#FEF2F2;border:1.5px solid #FCA5A5;border-radius:12px;padding:16px 18px;margin-bottom:24px;">
                        <p
                            style="margin:0 0 6px;font-size:11px;font-weight:700;color:#991B1B;text-transform:uppercase;letter-spacing:0.07em;">
                            Hampir Kedaluwarsa!
                        </p>
                        <p style="margin:0;font-size:13px;color:#B91C1C;line-height:1.6;">
                            Hanya tersisa <strong>{{ $hoursLeft }} jam</strong> lagi.<br>
                            Segera terima sebelum <strong>{{ $invitation->expires_at->format('d M Y, H:i') }}</strong>.
                        </p>
                    </div>
                @else
                    <div
                        style="background:#FFFBEB;border:1.5px solid #FCD34D;border-radius:12px;padding:16px 18px;margin-bottom:24px;">
                        <p
                            style="margin:0 0 6px;font-size:11px;font-weight:700;color:#92400E;text-transform:uppercase;letter-spacing:0.07em;">
                            Berlaku {{ $daysLeft }} Hari
                        </p>
                        <p style="margin:0;font-size:13px;color:#B45309;line-height:1.6;">
                            Undangan ini akan kedaluwarsa pada<br>
                            <strong>{{ $invitation->expires_at->format('d M Y, H:i') }}</strong>.
                        </p>
                    </div>
                @endif

                {{-- CTA Button --}}
                <a href="{{ route('invitations.accept', $invitation->token) }}"
                    style="display:block;text-align:center;padding:16px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#ffffff;border-radius:12px;text-decoration:none;font-weight:700;font-size:15px;letter-spacing:0.01em;margin-bottom:16px;box-shadow:0 4px 16px rgba(99,102,241,0.35);">
                    Accept Invitation
                </a>

                {{-- Link fallback --}}
                <div style="background:#F9FAFB;border-radius:10px;padding:12px 16px;text-align:center;">
                    <p style="margin:0 0 6px;font-size:11px;color:#9CA3AF;">Or copy this link:</p>
                    <p style="margin:0;font-size:11px;color:#6366f1;word-break:break-all;line-height:1.5;">
                        {{ route('invitations.accept', $invitation->token) }}
                    </p>
                </div>

            </div>

            {{-- Footer --}}
            <div style="padding:18px 36px;background:#F9FAFB;border-top:1px solid #F3F4F6;text-align:center;">
                <p style="margin:0 0 4px;font-size:12px;color:#6B7280;">
                    Questions? Contact
                    <a href="mailto:{{ $invitation->inviter->email }}"
                        style="color:#6366f1;text-decoration:none;font-weight:600;">
                        {{ $invitation->inviter->email }}
                    </a>
                </p>
                <p style="margin:0;font-size:11px;color:#D1D5DB;">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>

        </div>

    </div>
</body>

</html>
