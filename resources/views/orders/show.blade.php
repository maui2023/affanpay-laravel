@extends('layouts.app')

@section('content')
@php
    $paymentStatus = $order->payment?->status ?? 'pending';
    $orderStatus = $order->status;
    $isAutoChecking = in_array($paymentStatus, ['pending', 'processing'], true);
@endphp
<style>
    .payment-tracker {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 30%),
            radial-gradient(circle at top right, rgba(16, 185, 129, 0.18), transparent 28%),
            linear-gradient(135deg, #0f172a 0%, #1e293b 45%, #111827 100%);
    }

    .payment-tracker::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, transparent 10%, rgba(255, 255, 255, 0.08) 32%, transparent 52%);
        transform: translateX(-120%);
        animation: tracker-shimmer 4.5s linear infinite;
        pointer-events: none;
    }

    .transfer-rail {
        position: relative;
        height: 4px;
        border-radius: 9999px;
        background: rgba(148, 163, 184, 0.25);
        overflow: hidden;
    }

    .transfer-rail::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0) 16%,
            rgba(255, 255, 255, 0.55) 16%,
            rgba(255, 255, 255, 0.55) 24%,
            rgba(255, 255, 255, 0) 24%,
            rgba(255, 255, 255, 0) 40%
        );
        opacity: 0.7;
        animation: rail-flow 1.8s linear infinite;
    }

    .money-token {
        position: absolute;
        top: 50%;
        left: 0;
        width: 54px;
        height: 54px;
        margin-top: -27px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #064e3b;
        font-size: 1.125rem;
        font-weight: 700;
        background: linear-gradient(135deg, #fde68a 0%, #fbbf24 100%);
        box-shadow: 0 10px 30px rgba(251, 191, 36, 0.35);
        animation: money-travel 2.8s ease-in-out infinite;
    }

    .pulse-ring {
        animation: pulse-ring 2s ease-out infinite;
    }

    .status-glow {
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.08), 0 10px 35px rgba(15, 23, 42, 0.16);
    }

    @keyframes money-travel {
        0% { transform: translate(0, -50%) scale(0.92); opacity: 0.92; }
        50% { transform: translate(calc(100% - 54px), -50%) scale(1.05); opacity: 1; }
        100% { transform: translate(0, -50%) scale(0.92); opacity: 0.92; }
    }

    @keyframes rail-flow {
        0% { transform: translateX(-40%); }
        100% { transform: translateX(100%); }
    }

    @keyframes tracker-shimmer {
        0% { transform: translateX(-120%); }
        100% { transform: translateX(120%); }
    }

    @keyframes pulse-ring {
        0% { transform: scale(0.92); opacity: 0.55; }
        70% { transform: scale(1.18); opacity: 0; }
        100% { transform: scale(1.18); opacity: 0; }
    }
</style>

<div
    id="order-page"
    data-status-url="{{ route('orders.status', $order) }}"
    data-auto-checking="{{ $isAutoChecking ? 'true' : 'false' }}"
    class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8"
>
    <a href="{{ route('products.index') }}" class="text-blue-500 hover:text-blue-600 mb-4 inline-block">&larr; Back to Products</a>

    @if(session('payment_response'))
        <div class="mb-6 p-4 rounded-2xl {{ isset(session('payment_response')['success']) && session('payment_response')['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ isset(session('payment_response')['success']) && session('payment_response')['success'] ? 'Bill created successfully!' : 'Failed to create bill: ' . (json_encode(session('payment_response'))) }}
        </div>
    @endif

    @if(session('return_verification'))
        @php($verification = session('return_verification'))
        <div id="server-verification-banner" class="mb-6 p-4 rounded-2xl {{ ($verification['type'] ?? 'warning') === 'success' ? 'bg-green-100 text-green-800' : (($verification['type'] ?? 'warning') === 'error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
            <div class="font-semibold">{{ $verification['message'] ?? 'Payment status updated.' }}</div>
            @if(!empty($verification['reference']))
                <div class="mt-1 text-sm">Verified using {{ $verification['reference_label'] ?? 'Reference' }}: <span class="font-mono">{{ $verification['reference'] }}</span></div>
            @endif
        </div>
    @endif

    <div id="live-status-banner" class="mb-6 hidden rounded-2xl p-4"></div>

    <div id="payment-tracker-card" class="{{ $isAutoChecking ? '' : 'hidden ' }}payment-tracker status-glow mb-6 rounded-3xl p-6 text-white">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="max-w-lg">
                <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="pulse-ring absolute inline-flex h-full w-full rounded-full bg-emerald-300"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                    </span>
                    Live Payment Tracking
                </div>
                <h2 class="text-2xl font-bold leading-tight">Secure money transfer in progress</h2>
                <p id="tracker-message" class="mt-2 text-sm text-slate-200">
                    We are checking AffanPay automatically every few seconds. No manual refresh is needed.
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-slate-300">
                    <span class="rounded-full bg-white/10 px-3 py-1">Ref {{ strtoupper(substr($order->public_token, 0, 8)) }}</span>
                    <span class="rounded-full bg-white/10 px-3 py-1">Amount RM <span id="tracker-amount">{{ number_format($order->total_amount, 2) }}</span></span>
                    <span class="rounded-full bg-white/10 px-3 py-1">Auto-check every 4 seconds</span>
                </div>
            </div>

            <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur">
                <div class="mb-6 flex items-center justify-between text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">
                    <span>Sender</span>
                    <span>Receiver</span>
                </div>
                <div class="relative mb-6 flex items-center justify-between gap-4">
                    <div class="z-10 flex h-20 w-20 flex-col items-center justify-center rounded-3xl bg-blue-500/20 text-center">
                        <span class="text-xl">A</span>
                        <span class="mt-1 text-[11px] text-blue-100">Buyer</span>
                    </div>
                    <div class="absolute left-16 right-16 top-1/2 -translate-y-1/2">
                        <div class="transfer-rail"></div>
                        <div class="money-token">RM</div>
                    </div>
                    <div class="z-10 flex h-20 w-20 flex-col items-center justify-center rounded-3xl bg-emerald-500/20 text-center">
                        <span class="text-xl">B</span>
                        <span class="mt-1 text-[11px] text-emerald-100">Merchant</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl bg-white/10 p-3">
                        <div class="text-slate-300">Current State</div>
                        <div id="tracker-state" class="mt-1 font-semibold text-white">{{ ucfirst($paymentStatus) }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 p-3">
                        <div class="text-slate-300">Payment Ref</div>
                        <div id="tracker-reference" class="mt-1 truncate font-mono text-xs text-white">{{ $order->payment?->payment_reference ?: ($order->payment?->affanpay_transaction_id ?: 'Waiting...') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6">Order Details</h1>

        <div class="space-y-4 mb-8">
            <div>
                <span class="text-gray-600">Order Reference:</span>
                <span class="font-semibold font-mono">{{ strtoupper(substr($order->public_token, 0, 8)) }}</span>
            </div>
            <div>
                <span class="text-gray-600">Product:</span>
                <span class="font-semibold">{{ $order->product->name }}</span>
            </div>
            <div>
                <span class="text-gray-600">Quantity:</span>
                <span class="font-semibold">{{ $order->quantity }}</span>
            </div>
            <div>
                <span class="text-gray-600">Total Amount:</span>
                <span id="order-amount" class="font-semibold text-green-600 text-xl">RM {{ number_format($order->total_amount, 2) }}</span>
            </div>
            <div>
                <span class="text-gray-600">Customer Name:</span>
                <span class="font-semibold">{{ $order->customer_name }}</span>
            </div>
            <div>
                <span class="text-gray-600">Customer Email:</span>
                <span class="font-semibold">{{ $order->customer_email }}</span>
            </div>
            @if($order->customer_phone)
                <div>
                    <span class="text-gray-600">Customer Phone:</span>
                    <span class="font-semibold">{{ $order->customer_phone }}</span>
                </div>
            @endif
            <div>
                <span class="text-gray-600">Order Status:</span>
                <span id="order-status-badge" class="font-semibold px-3 py-1 rounded-full {{ $orderStatus === 'paid' ? 'bg-green-100 text-green-800' : ($orderStatus === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    <span id="order-status-text">{{ ucfirst($orderStatus) }}</span>
                </span>
            </div>
            <div>
                <span class="text-gray-600">Payment Status:</span>
                <span id="payment-status-badge" class="font-semibold px-3 py-1 rounded-full {{ $paymentStatus === 'paid' ? 'bg-green-100 text-green-800' : ($paymentStatus === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    <span id="payment-status-text">{{ ucfirst($paymentStatus) }}</span>
                </span>
            </div>
            <div>
                <span class="text-gray-600">Payment Reference:</span>
                <span id="payment-reference-text" class="font-semibold font-mono">{{ $order->payment?->payment_reference ?: 'Not available yet' }}</span>
            </div>
            <div>
                <span class="text-gray-600">Bill Reference:</span>
                <span id="bill-reference-text" class="font-semibold font-mono">{{ $order->payment?->affanpay_transaction_id ?: 'Not available yet' }}</span>
            </div>
        </div>

        <div id="payment-actions">
            @if($order->payment && $order->payment->status !== 'paid')
                <div class="space-y-4">
                    @if(in_array($order->payment->status, ['pending', 'processing', 'failed']))
                        <form id="manual-check-form" action="{{ route('orders.check-status', $order) }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full rounded-2xl border border-gray-300 bg-white px-6 py-3 font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50">
                                Check Latest Payment Status Manually
                            </button>
                        </form>
                    @endif

                    @if($order->payment->payment_response && isset($order->payment->payment_response['url']) && in_array($order->payment->status, ['pending', 'processing', 'failed']))
                        <a id="continue-payment-link" href="{{ $order->payment->payment_response['url'] }}" target="_blank" class="w-full rounded-2xl bg-green-500 px-6 py-3 font-semibold text-white text-center block transition hover:bg-green-600">
                            Continue Payment with AffanPay
                        </a>
                    @elseif(in_array($order->payment->status, ['pending', 'failed']))
                        <form id="retry-payment-form" action="{{ route('orders.retry-payment', $order) }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full rounded-2xl bg-blue-500 px-6 py-3 font-semibold text-white transition hover:bg-blue-600">
                                Retry Payment
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const pageRoot = document.getElementById('order-page');
        const statusUrl = pageRoot ? pageRoot.dataset.statusUrl : null;
        const autoChecking = pageRoot ? pageRoot.dataset.autoChecking === 'true' : false;
        const pollMs = 4000;
        const liveBanner = document.getElementById('live-status-banner');
        const trackerCard = document.getElementById('payment-tracker-card');
        const trackerMessage = document.getElementById('tracker-message');
        const trackerState = document.getElementById('tracker-state');
        const trackerReference = document.getElementById('tracker-reference');
        const trackerAmount = document.getElementById('tracker-amount');
        const orderStatusText = document.getElementById('order-status-text');
        const orderStatusBadge = document.getElementById('order-status-badge');
        const paymentStatusText = document.getElementById('payment-status-text');
        const paymentStatusBadge = document.getElementById('payment-status-badge');
        const paymentReferenceText = document.getElementById('payment-reference-text');
        const billReferenceText = document.getElementById('bill-reference-text');
        const continuePaymentLink = document.getElementById('continue-payment-link');
        const retryPaymentForm = document.getElementById('retry-payment-form');
        const manualCheckForm = document.getElementById('manual-check-form');

        let pollingTimer = null;
        let isFetching = false;

        const titleCase = (value) => value ? value.charAt(0).toUpperCase() + value.slice(1) : 'Pending';

        const badgeClass = (status) => {
            if (status === 'paid') return 'bg-green-100 text-green-800';
            if (status === 'failed' || status === 'cancelled') return 'bg-red-100 text-red-800';
            return 'bg-yellow-100 text-yellow-800';
        };

        const bannerClass = (type) => {
            if (type === 'success') return 'bg-green-100 text-green-800';
            if (type === 'error') return 'bg-red-100 text-red-800';
            return 'bg-blue-50 text-blue-800 border border-blue-100';
        };

        const setBadge = (badgeEl, textEl, status) => {
            if (!badgeEl || !textEl) return;
            badgeEl.className = `font-semibold px-3 py-1 rounded-full ${badgeClass(status)}`;
            textEl.textContent = titleCase(status);
        };

        const setBanner = (message, type) => {
            if (!liveBanner || !message) return;
            liveBanner.className = `mb-6 rounded-2xl p-4 ${bannerClass(type)}`;
            liveBanner.textContent = message;
            liveBanner.classList.remove('hidden');
        };

        const hideTrackerIfDone = (paymentStatus) => {
            if (!trackerCard) return;
            if (paymentStatus === 'paid' || paymentStatus === 'failed') {
                trackerCard.classList.add('hidden');
            } else {
                trackerCard.classList.remove('hidden');
            }
        };

        const updateActions = (payload) => {
            const paymentStatus = payload.payment_status;

            if (manualCheckForm) {
                manualCheckForm.classList.toggle('hidden', paymentStatus === 'paid');
            }

            if (continuePaymentLink) {
                const showContinue = ['pending', 'processing', 'failed'].includes(paymentStatus) && !!payload.payment_url;
                continuePaymentLink.classList.toggle('hidden', !showContinue);
                if (showContinue) {
                    continuePaymentLink.href = payload.payment_url;
                }
            }

            if (retryPaymentForm) {
                retryPaymentForm.classList.toggle('hidden', paymentStatus !== 'failed');
            }
        };

        const applyPayload = (payload) => {
            setBadge(orderStatusBadge, orderStatusText, payload.order_status);
            setBadge(paymentStatusBadge, paymentStatusText, payload.payment_status);

            if (paymentReferenceText) {
                paymentReferenceText.textContent = payload.payment_reference || 'Not available yet';
            }
            if (billReferenceText) {
                billReferenceText.textContent = payload.bill_reference || 'Not available yet';
            }
            if (trackerState) {
                trackerState.textContent = titleCase(payload.payment_status);
            }
            if (trackerReference) {
                trackerReference.textContent = payload.payment_reference || payload.bill_reference || 'Waiting...';
            }
            if (trackerAmount && payload.amount) {
                trackerAmount.textContent = payload.amount;
            }
            if (trackerMessage && payload.message) {
                trackerMessage.textContent = payload.message;
            }

            updateActions(payload);
            hideTrackerIfDone(payload.payment_status);

            if (payload.message) {
                setBanner(payload.message, payload.verification_type || (payload.payment_status === 'paid' ? 'success' : (payload.payment_status === 'failed' ? 'error' : 'info')));
            }

            if (payload.terminal && pollingTimer) {
                window.clearInterval(pollingTimer);
                pollingTimer = null;
            }
        };

        const fetchStatus = async () => {
            if (isFetching) return;
            isFetching = true;

            try {
                const response = await fetch(statusUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                applyPayload(payload);
            } catch (error) {
                setBanner('Live payment tracking is retrying. We could not fetch the latest status just now.', 'info');
            } finally {
                isFetching = false;
            }
        };

        if (autoChecking && statusUrl) {
            fetchStatus();
            pollingTimer = window.setInterval(fetchStatus, pollMs);
        }
    });
</script>
@endsection
