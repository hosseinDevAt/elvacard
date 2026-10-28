<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>در حال به‌روزرسانی — الواکارت</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <style>
        @font-face {
            font-family: 'Yekan Bakh';
            src: url('/fonts/Yekan_Bakh_Fanum_Regular.TTF') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Yekan Bakh';
            src: url('/fonts/Yekan_Bakh_Fanum_Medium.TTF') format('truetype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Yekan Bakh';
            src: url('/fonts/Yekan_Bakh_Fanum_Bold.TTF') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Yekan Bakh';
            src: url('/fonts/Yekan_Bakh_Fanum_Heavy.TTF') format('truetype');
            font-weight: 900;
            font-style: normal;
            font-display: swap;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Yekan Bakh', -apple-system, BlinkMacSystemFont, 'Segoe UI', Tahoma, sans-serif;
            background-color: #010619;
            background-image:
                radial-gradient(circle at 50% 15%, rgba(255, 222, 91, 0.08) 0%, transparent 45%),
                radial-gradient(circle at 80% 85%, rgba(21, 34, 68, 0.45) 0%, transparent 50%),
                radial-gradient(circle at 15% 75%, rgba(15, 23, 42, 0.6) 0%, transparent 45%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        .container {
            width: 100%;
            max-width: 620px;
            margin: 0 auto;
        }

        .card {
            background: linear-gradient(180deg, rgba(7, 14, 36, 0.85) 0%, rgba(3, 8, 22, 0.95) 100%);
            border: 1px solid #152244;
            border-radius: 28px;
            padding: 3rem 2.25rem;
            box-shadow:
                0 25px 60px -15px rgba(0, 0, 0, 0.7),
                0 0 40px -10px rgba(255, 222, 91, 0.1);
            backdrop-filter: blur(12px);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 222, 91, 0.35) 50%, transparent 100%);
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: #ffde5b;
            color: #010619;
            box-shadow: 0 8px 24px -4px rgba(255, 222, 91, 0.35);
            margin-bottom: 1.75rem;
            transition: transform 0.3s ease;
        }

        .brand-badge:hover {
            transform: scale(1.04);
        }

        .brand-badge svg {
            width: 34px;
            height: 34px;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 222, 91, 0.1);
            border: 1px solid rgba(255, 222, 91, 0.25);
            color: #ffde5b;
            padding: 0.375rem 0.95rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 700;
            margin-bottom: 1.75rem;
            letter-spacing: -0.01em;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background-color: #ffde5b;
            border-radius: 50%;
            box-shadow: 0 0 8px #ffde5b;
            animation: pulse-glow 2s infinite ease-in-out;
        }

        .illustration {
            width: 140px;
            height: 90px;
            margin: 0 auto 1.75rem;
            opacity: 0.95;
        }

        h1 {
            font-size: 1.625rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 0.75rem;
            letter-spacing: -0.025em;
            line-height: 1.35;
        }

        .quote {
            font-size: 0.9375rem;
            font-weight: 500;
            color: #ffde5b;
            opacity: 0.92;
            margin-bottom: 1.25rem;
            padding: 0 0.5rem;
        }

        .main-message {
            font-size: 0.9375rem;
            font-weight: 400;
            color: #94a3b8;
            margin-bottom: 1.75rem;
            line-height: 1.85;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .divider {
            height: 1px;
            width: 80px;
            background: #152244;
            margin: 0 auto 1.5rem;
        }

        .closing {
            font-size: 0.875rem;
            font-weight: 700;
            color: #cbd5e1;
            letter-spacing: -0.01em;
        }

        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: #64748b;
        }

        @keyframes pulse-glow {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.45;
                transform: scale(0.85);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .status-dot {
                animation: none;
            }
            .brand-badge {
                transition: none;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }
            .card {
                padding: 2.25rem 1.25rem;
                border-radius: 22px;
            }
            h1 {
                font-size: 1.375rem;
            }
            .quote {
                font-size: 0.875rem;
            }
            .main-message {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <main class="card" role="main">
            <!-- Brand Logo -->
            <div class="brand-badge" aria-hidden="true">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="4" y="14" width="56" height="38" rx="10" fill="#010619"/>
                    <rect x="14" y="24" width="12" height="10" rx="2.5" fill="#ffde5b"/>
                    <rect x="30" y="24" width="20" height="4.5" rx="2.25" fill="#ffde5b" opacity="0.9"/>
                    <path d="M12 44h14v5h-14z" fill="#ffde5b" opacity="0.95"/>
                    <path d="M24 26.5l18 19" stroke="#ffde5b" stroke-width="3" stroke-linecap="round"/>
                </svg>
            </div>

            <!-- Status Indicator -->
            <div>
                <div class="status-pill" role="status" aria-live="polite">
                    <span class="status-dot" aria-hidden="true"></span>
                    <span>در حال بهبود تجربهٔ شما</span>
                </div>
            </div>

            <!-- Craftsmanship / Refinement Illustration -->
            <div class="illustration" aria-hidden="true">
                <svg viewBox="0 0 160 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Subtle ambient aura -->
                    <circle cx="80" cy="50" r="42" fill="url(#goldAura)" opacity="0.25"/>
                    <!-- Smart card silhouette -->
                    <rect x="25" y="18" width="110" height="64" rx="12" fill="#070e24" stroke="#ffde5b" stroke-width="1.5" stroke-opacity="0.6"/>
                    <!-- Metallic accent lines -->
                    <line x1="25" y1="38" x2="135" y2="38" stroke="#152244" stroke-width="1"/>
                    <rect x="38" y="48" width="20" height="16" rx="3.5" fill="#ffde5b" fill-opacity="0.85"/>
                    <path d="M43 48v16M48 48v16M53 48v16M38 56h20" stroke="#010619" stroke-width="0.8" stroke-linecap="round"/>
                    <!-- NFC wave / precision curves -->
                    <path d="M116 46a10 10 0 0 1 0 14" stroke="#ffde5b" stroke-width="1.75" stroke-linecap="round" opacity="0.85"/>
                    <path d="M122 41a18 18 0 0 1 0 24" stroke="#ffde5b" stroke-width="1.75" stroke-linecap="round" opacity="0.55"/>
                    <!-- Craftsmanship sparkle points -->
                    <path d="M128 20l2 4 4 2-4 2-2 4-2-4-4-2 4-2 2-4z" fill="#ffde5b" opacity="0.9"/>
                    <path d="M28 72l1.5 3 3 1.5-3 1.5-1.5 3-1.5-3-3-1.5 3-1.5 1.5-3z" fill="#ffde5b" opacity="0.75"/>
                    <defs>
                        <radialGradient id="goldAura" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(80 50) rotate(90) scale(42)">
                            <stop stop-color="#ffde5b"/>
                            <stop offset="1" stop-color="#ffde5b" stop-opacity="0"/>
                        </radialGradient>
                    </defs>
                </svg>
            </div>

            <!-- Persian Copy -->
            <h1>داریم چیزی زیباتر آماده می‌کنیم</h1>
            <p class="quote">«گاهی برای ساختن چیزی بهتر، باید مکثی کرد و با دقت بیشتری ادامه داد.»</p>
            <p class="main-message">
                الواکارت موقتاً برای بهبود تجربهٔ شما در حال به‌روزرسانی است. به‌زودی بازمی‌گردیم؛ با تجربه‌ای بهتر و جزئیاتی که ارزش انتظار دارند.
            </p>

            <div class="divider" aria-hidden="true"></div>

            <p class="closing">از شکیبایی شما سپاسگزاریم.</p>
        </main>

        <footer class="footer">
            <p lang="en" dir="ltr">© {{ date('Y') }} ElvaCard. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
