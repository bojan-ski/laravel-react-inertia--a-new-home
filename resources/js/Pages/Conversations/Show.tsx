import { useEcho } from '@laravel/echo-react'
import { Head, Link, usePage } from '@inertiajs/react'
import { ChevronLeft } from 'lucide-react'
import { useEffect, useMemo, useRef, useState } from 'react'
import MessageBubble from '@/Components/Conversations/MessageBubble'
import MessageComposer from '@/Components/Conversations/MessageComposer'
import ReportMessageDialog from '@/Components/Conversations/ReportMessageDialog'
import ListingsLayout from '@/Layouts/ListingsLayout'
import type { ChatMessageBroadcast, ChatMessageData, ConversationShowPageProps } from '@/types/conversation'
import type { SharedProps } from '@/types/shared'

export default function Show({ conversation, canReply, translations }: ConversationShowPageProps) {
    const page = usePage<SharedProps>()
    const currentUserId = page.props.auth.user?.id ?? null
    const thread = translations.thread

    const [liveMessages, setLiveMessages] = useState<ChatMessageData[]>([])
    const [reportMessage, setReportMessage] = useState<ChatMessageData | null>(null)

    const messages = useMemo(() => {
        const seen = new Set(conversation.messages.map((message) => message.id))
        const extra = liveMessages.filter((message) => !seen.has(message.id))
        return [...conversation.messages, ...extra]
    }, [conversation.messages, liveMessages])

    useEcho<ChatMessageBroadcast>(`conversation.${conversation.id}`, '.ChatMessageSent', (event) => {
        setLiveMessages((previous) => {
            if (previous.some((message) => message.id === event.id)) {
                return previous
            }

            return [
                ...previous,
                {
                    id: event.id,
                    senderId: event.senderId,
                    senderName: event.senderName,
                    body: event.body,
                    createdAt: event.createdAt,
                },
            ]
        })
    })

    const bottomRef = useRef<HTMLDivElement>(null)

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' })
    }, [messages.length])

    return (
        <ListingsLayout>
            <Head title={conversation.listing.title} />

            <div className="mx-auto flex max-w-4xl flex-col px-6 py-6">
                <Link
                    href="/conversations"
                    className="inline-flex items-center gap-1 text-sm text-text-soft transition-colors hover:text-text"
                >
                    <ChevronLeft className="size-4" />
                    {thread.back}
                </Link>

                <div className="mt-4 rounded-2xl border border-border bg-surface">
                    <div className="border-b border-border px-6 py-4">
                        {conversation.listing.slug ? (
                            <Link
                                href={`/listings/${conversation.listing.slug}`}
                                className="font-semibold text-text transition-colors hover:text-brand"
                            >
                                {conversation.listing.title}
                            </Link>
                        ) : (
                            <p className="font-semibold text-text">{conversation.listing.title}</p>
                        )}
                        <p className="text-sm text-text-muted">
                            {thread.with}{' '}
                            <span className="font-medium text-text">{conversation.otherParticipant.name}</span>
                        </p>
                    </div>

                    <div className="flex max-h-[60vh] flex-col gap-3 overflow-y-auto px-6 py-6">
                        {messages.length === 0 ? (
                            <p className="py-12 text-center text-sm text-text-soft">{thread.empty}</p>
                        ) : (
                            messages.map((message, index) => {
                                const previous = messages[index - 1]
                                const showName = !previous || previous.senderId !== message.senderId

                                return (
                                    <MessageBubble
                                        key={message.id}
                                        message={message}
                                        isOwn={message.senderId === currentUserId}
                                        showName={showName}
                                        reportLabel={translations.report.action}
                                        onReport={message.senderId !== null ? () => setReportMessage(message) : undefined}
                                    />
                                )
                            })
                        )}
                        <div ref={bottomRef} />
                    </div>

                    {canReply ? (
                        <MessageComposer conversationId={conversation.id} translations={thread} />
                    ) : (
                        <p className="border-t border-border bg-surface-muted px-6 py-4 text-sm text-text-soft">
                            {thread.read_only}
                        </p>
                    )}
                </div>
            </div>

            <ReportMessageDialog
                open={reportMessage !== null}
                onClose={() => setReportMessage(null)}
                messageId={reportMessage?.id ?? 0}
                preview={reportMessage?.body.slice(0, 100) ?? ''}
                translations={translations.report}
            />
        </ListingsLayout>
    )
}
