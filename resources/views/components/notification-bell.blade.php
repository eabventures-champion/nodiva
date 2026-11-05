@php
    $notificationService = app(\App\Services\DashboardNotificationService::class);
    $activeOrg = $organization ?? auth()->user()?->currentMembership?->organization ?? auth()->user()?->activeMemberships()->first()?->organization;
    $notifications = $notificationService->getNotifications($activeOrg, auth()->user());
@endphp

<div class="relative" x-data="{
    open: false,
    notifications: {{ Js::from($notifications) }},
    readIds: JSON.parse(localStorage.getItem('nodiva_read_notifications') || '[]'),
    clearedIds: JSON.parse(localStorage.getItem('nodiva_cleared_notifications') || '[]'),
    
    get activeNotifications() {
        return this.notifications.filter(n => !this.clearedIds.includes(n.id));
    },

    get unreadCount() {
        return this.activeNotifications.filter(n => !this.readIds.includes(n.id)).length;
    },
    
    markAllRead() {
        this.readIds = [...new Set([...this.readIds, ...this.activeNotifications.map(n => n.id)])];
        localStorage.setItem('nodiva_read_notifications', JSON.stringify(this.readIds));
    },

    clearAll() {
        this.clearedIds = [...new Set([...this.clearedIds, ...this.notifications.map(n => n.id)])];
        localStorage.setItem('nodiva_cleared_notifications', JSON.stringify(this.clearedIds));
    },

    clearItem(id, event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        if (!this.clearedIds.includes(id)) {
            this.clearedIds.push(id);
            localStorage.setItem('nodiva_cleared_notifications', JSON.stringify(this.clearedIds));
        }
    },
    
    markAsRead(id, url) {
        if (!this.readIds.includes(id)) {
            this.readIds.push(id);
            localStorage.setItem('nodiva_read_notifications', JSON.stringify(this.readIds));
        }
        if (url) {
            window.location.href = url;
        }
    },

    isRead(id) {
        return this.readIds.includes(id);
    }
}">
    <!-- Bell Trigger Button -->
    <button type="button" 
            @click="open = !open" 
            title="Institutional Notifications"
            class="relative p-2 text-[#D5DEDF] hover:text-white bg-[#193B49] hover:bg-[#167C78]/80 border border-[#D5DEDF]/20 hover:border-[#167C78] rounded-sm transition focus:outline-none flex items-center justify-center">
        <!-- SVG Bell -->
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        <!-- Unread Badge Counter -->
        <span x-show="unreadCount > 0" 
              x-text="unreadCount"
              style="display: none;"
              class="absolute -top-1.5 -right-1.5 px-1.5 py-0.2 bg-[#B42332] text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center border-2 border-[#102A36] shadow-sm animate-pulse">
        </span>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         @click.away="open = false" 
         @keydown.escape.window="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 transform scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 transform scale-95 -translate-y-1"
         style="display: none;"
         class="absolute right-0 mt-2 w-80 sm:w-96 md:w-[420px] bg-white border border-[#D5DEDF] shadow-xl rounded-sm z-50 text-[#172A35] overflow-hidden">
        
        <!-- Header -->
        <div class="px-4 py-3 bg-[#102A36] text-white flex items-center justify-between border-b border-[#193B49]">
            <div class="flex items-center gap-2">
                <span class="p-1 bg-[#167C78]/25 text-teal-300 rounded-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </span>
                <span class="font-bold text-xs uppercase tracking-wider text-[#F5F3ED]">Notifications</span>
                <span x-show="unreadCount > 0" 
                      x-text="unreadCount + ' new'" 
                      class="text-[10px] font-mono font-bold bg-[#167C78] text-white px-2 py-0.5 rounded-full shadow-2xs"></span>
            </div>
            <div class="flex items-center gap-1.5">
                <button type="button" 
                        @click="markAllRead()" 
                        x-show="unreadCount > 0"
                        style="display: none;"
                        class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-[#D5DEDF] hover:text-white bg-[#193B49]/80 hover:bg-[#193B49] border border-[#D5DEDF]/20 hover:border-[#167C78] rounded-xs transition inline-flex items-center gap-1 shadow-2xs cursor-pointer">
                    <svg class="w-3 h-3 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Mark all read</span>
                </button>
            </div>
        </div>

        <!-- Notification List -->
        <div class="max-h-96 overflow-y-auto divide-y divide-[#D5DEDF]/50">
            <template x-if="activeNotifications.length === 0">
                <div class="p-8 text-center space-y-2">
                    <div class="w-10 h-10 mx-auto rounded-full bg-[#E8F0EF] text-[#167C78] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-[#102A36]">All caught up!</p>
                    <p class="text-[11px] text-[#61727D]">No pending institutional notifications at this moment.</p>
                </div>
            </template>

            <template x-for="item in activeNotifications" :key="item.id">
                <div @click="markAsRead(item.id, item.url)"
                     class="p-3.5 hover:bg-[#F5F3ED] transition cursor-pointer flex items-start space-x-3 group relative"
                     :class="isRead(item.id) ? 'opacity-65 bg-white' : 'bg-[#E8F0EF]/30'">
                    
                    <!-- Status Icon -->
                    <div class="flex-shrink-0 mt-0.5">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs transition-colors"
                             :class="isRead(item.id) ? 'bg-[#D5DEDF] text-[#61727D]' : 'bg-[#167C78]/15 text-[#167C78] group-hover:bg-[#167C78] group-hover:text-white'">
                            <template x-if="item.icon === 'order' || item.type === 'order'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </template>
                            <template x-if="item.icon === 'truck' || item.type === 'transit'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                            </template>
                            <template x-if="item.icon === 'user-check' || item.type === 'access_request'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </template>
                            <template x-if="item.icon === 'file-check' || item.type === 'result'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                            <template x-if="item.icon === 'chat' || item.type === 'communication'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </template>
                            <template x-if="!['order', 'transit', 'access_request', 'result', 'communication'].includes(item.type) && !['order', 'truck', 'user-check', 'file-check', 'chat'].includes(item.icon)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </template>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-grow min-w-0 space-y-1.5">
                        <!-- Badge & Timestamp -->
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-xs"
                                  :class="item.badge_class"
                                  x-text="item.badge"></span>
                            <span class="text-[10px] text-[#61727D] font-mono shrink-0" x-text="item.time"></span>
                        </div>

                        <!-- Title / Order Identifier -->
                        <div>
                            <template x-if="item.order_reference">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-xs font-bold text-[#102A36] group-hover:text-[#167C78] transition">Referral</span>
                                    <span class="font-mono text-[11px] font-bold text-[#167C78] bg-[#167C78]/10 px-1.5 py-0.5 rounded-xs border border-[#167C78]/25 tracking-wide"
                                          x-text="item.order_reference"></span>
                                </div>
                            </template>
                            <template x-if="!item.order_reference">
                                <h4 class="text-xs font-bold text-[#102A36] group-hover:text-[#167C78] transition truncate"
                                    x-text="item.title"></h4>
                            </template>
                        </div>

                        <!-- Route Flow or Context Message -->
                        <template x-if="item.from_facility && item.to_facility">
                            <div class="flex items-center gap-1.5 text-[11px] bg-white/90 border border-[#D5DEDF] px-2.5 py-1.5 rounded-xs shadow-2xs">
                                <!-- Origin Hospital -->
                                <div class="flex items-center gap-1.5 min-w-0 flex-1" :title="'Referring: ' + item.from_facility">
                                    <svg class="w-3.5 h-3.5 text-[#61727D] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span class="font-medium text-[#102A36] truncate" x-text="item.from_facility"></span>
                                </div>

                                <!-- Arrow Divider -->
                                <svg class="w-3.5 h-3.5 text-[#167C78] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>

                                <!-- Destination Laboratory -->
                                <div class="flex items-center gap-1.5 min-w-0 flex-1 justify-end" :title="'Laboratory: ' + item.to_facility">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                         :class="item.to_facility.includes('Broadcast') ? 'text-blue-600' : 'text-[#167C78]'">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                    <span class="font-bold truncate"
                                          :class="item.to_facility.includes('Broadcast') ? 'text-blue-700 italic' : 'text-[#167C78]'"
                                          x-text="item.to_facility"></span>
                                </div>
                            </div>
                        </template>

                        <template x-if="!item.from_facility || !item.to_facility">
                            <p class="text-[11px] text-[#61727D] line-clamp-2 leading-tight"
                               x-text="item.message"></p>
                        </template>
                    </div>

                    <!-- Actions: unread dot & dismiss button -->
                    <div class="flex-shrink-0 flex items-center gap-1.5 self-center">
                        <span x-show="!isRead(item.id)" class="w-2 h-2 rounded-full bg-[#167C78] block"></span>
                        <button type="button" @click.stop="clearItem(item.id, $event)"
                                class="text-[#61727D] hover:text-[#B42332] p-1 rounded-xs opacity-40 hover:opacity-100 transition cursor-pointer"
                                title="Dismiss notification">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-3.5 py-2.5 bg-[#F5F3ED] border-t border-[#D5DEDF] flex items-center justify-between gap-2">
            <span class="text-[10px] text-[#61727D] tracking-wide truncate">
                NODIVA Real-time Institutional Network Stream
            </span>
            <button type="button" 
                    @click="clearAll()" 
                    x-show="activeNotifications.length > 0"
                    style="display: none;"
                    class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-[#B42332] hover:text-white bg-rose-50 hover:bg-[#B42332] border border-rose-200/80 hover:border-[#B42332] rounded-xs transition inline-flex items-center gap-1 cursor-pointer shrink-0 shadow-2xs"
                    title="Clear all notifications from view">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Clear all</span>
            </button>
        </div>
    </div>
</div>
