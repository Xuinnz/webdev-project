@extends('layouts.patientCommon')

@section('title', 'Chat')

@section('content')
    <div x-data="{ draft: '', messages: @js($messages) }">
        <h1 class="page-title animate-unicare-in stagger-1">Chat</h1>

        <div class="chat-grid">
            <div class="unicare-card-dark unicare-card-dark--chat animate-unicare-scale-in stagger-2">
                <div class="chat-header">
                    <p class="chat-header-name">{{ $doctor_name }}</p>
                    <p class="chat-header-status">Online</p>
                </div>

                <div class="chat-messages">
                    <template x-for="(message, index) in messages" :key="message.id">
                        <div
                            class="chat-bubble"
                            :class="message.sender_name === '{{ $doctor_name }}'
                                ? 'chat-bubble-incoming animate-unicare-in-left'
                                : 'chat-bubble-outgoing animate-unicare-in-right'"
                            x-bind:class="'stagger-' + Math.min(index + 1, 8)"
                        >
                            <p class="chat-bubble-from" x-text="message.sender_name"></p>
                            <p class="chat-bubble-text" x-text="message.body"></p>
                            <p class="chat-bubble-time" x-text="message.created_at"></p>
                        </div>
                    </template>
                </div>
            </div>

            <div class="chat-sidebar animate-unicare-in stagger-4">
                <div class="glass-panel">
                    <h2 class="section-title">Quick Replies</h2>
                    <div class="quick-replies">
                        @foreach (['Thank you, doctor.', 'I have a follow-up question.', 'Can we reschedule?'] as $index => $reply)
                            <button
                                type="button"
                                class="quick-reply-btn animate-unicare-in stagger-{{ $index + 1 }}"
                                @click="draft = '{{ $reply }}'"
                            >
                                {{ $reply }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="glass-panel animate-unicare-in stagger-5">
                    <label for="chat-draft" class="form-label">Message</label>
                    <textarea
                        id="chat-draft"
                        x-model="draft"
                        rows="4"
                        class="form-textarea"
                        placeholder="Type your message..."
                    ></textarea>
                    <button
                        type="button"
                        class="unicare-btn-primary unicare-btn-primary--block"
                        @click="
                            if (! draft.trim()) return;
                            messages.push({
                                id: Date.now(),
                                conversation_id: {{ $conversation?->id ?? 'null' }},
                                sender_id: null,
                                sender_name: 'You',
                                body: draft,
                                is_read: true,
                                created_at: 'Now'
                            });
                            draft = '';
                        "
                    >
                        Send Message
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
