import { useEcho } from '@laravel/echo-react'
import { Head, usePage } from '@inertiajs/react'
import { Fragment, useEffect, useMemo, useRef, useState } from 'react'
import ConversationInbox from '@/Components/Conversations/ConversationInbox'
import { formatDaySeparator, isSameDay } from '@/Components/Conversations/chatTime'
import MessageBubble from '@/Components/Conversations/MessageBubble'
import MessageComposer from '@/Components/Conversations/MessageComposer'
import ReportMessageDialog from '@/Components/Conversations/ReportMessageDialog'
import ThreadHeader from '@/Components/Conversations/ThreadHeader'
import FlashBanner from '@/Components/UI/FlashBanner'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ChatMessageBroadcast, ChatMessageData, ConversationShowPageProps } from '@/types/conversation'
import type { SharedProps } from '@/types/shared'

export default function Show({ conversation, conversations, canReply, translations }: ConversationShowPageProps) {
    const { auth, flash, ui } = usePage<SharedProps>().props
    const currentUserId = auth.user?.id ?? null
    const thread = translations.thread

    const [liveMessages, setLiveMessages] = useState<ChatMessageData[]>([])
    const [reportMessage, setReportMessage] = useState<ChatMessageData | null>(null)
    const [dismissedFlash, setDismissedFlash] = useState<SharedProps['flash'] | null>(null)
    const flashMessage = flash.success ?? flash.error

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

    const threadRef = useRef<HTMLDivElement>(null)
    const renderedCount = useRef(messages.length)

    useEffect(() => {
        const element = threadRef.current

        if (element) {
            element.scrollTop = element.scrollHeight
        }
    }, [conversation.id])

    useEffect(() => {
        const element = threadRef.current

        if (element && messages.length > renderedCount.current) {
            element.scrollTo({ top: element.scrollHeight, behavior: 'smooth' })
        }

        renderedCount.current = messages.length
    }, [messages.length])

    return (
        <PublicLayout back={{ href: '/conversations', label: thread.back }} footer={false} fill="always">
            <Head title={conversation.listing.title} />

            <div className="grid min-h-0 flex-1 grid-rows-1 lg:grid-cols-[460px_minmax(0,1fr)]">
                <div className="hidden min-h-0 border-r border-rule lg:flex lg:flex-col">
                    <ConversationInbox
                        conversations={conversations}
                        translations={translations}
                        selectedId={conversation.id}
                        titleAs="h2"
                        className="flex-1"
                    />
                </div>

                <section className="flex min-h-0 flex-col">
                    <ThreadHeader conversation={conversation} translations={translations} />

                    {flashMessage && dismissedFlash !== flash && (
                        <FlashBanner
                            message={flashMessage}
                            variant={flash.success ? 'success' : 'error'}
                            className="mx-5 mt-4 lg:mt-6 lg:mr-10 lg:ml-8 xl:mr-16 xl:ml-12"
                            onDismiss={() => setDismissedFlash(flash)}
                            dismissLabel={ui.dismiss}
                        />
                    )}

                    <div
                        ref={threadRef}
                        className="flex min-h-0 flex-1 flex-col gap-3.5 overflow-y-auto p-5 lg:gap-4.5 lg:pt-8 lg:pr-10 lg:pb-6 lg:pl-8 xl:pr-16 xl:pl-12"
                    >
                        {messages.length === 0 ? (
                            <p className="m-auto text-center text-sm text-ink-muted">{thread.empty}</p>
                        ) : (
                            messages.map((message, index) => {
                                const previous = messages[index - 1]
                                const startsDay = !previous || !isSameDay(previous.createdAt, message.createdAt)
                                const startsRun = startsDay || previous.senderId !== message.senderId
                                const isOwn = message.senderId === currentUserId

                                return (
                                    <Fragment key={message.id}>
                                        {startsDay && (
                                            <p className="self-center text-[11px] tracking-widest text-ink/55 uppercase lg:py-2 lg:text-xs">
                                                {formatDaySeparator(message.createdAt, translations)}
                                            </p>
                                        )}
                                        <MessageBubble
                                            message={message}
                                            isOwn={isOwn}
                                            showName={startsRun}
                                            reportLabel={translations.report.action}
                                            onReport={
                                                !isOwn && message.senderId !== null
                                                    ? () => setReportMessage(message)
                                                    : undefined
                                            }
                                        />
                                    </Fragment>
                                )
                            })
                        )}
                    </div>

                    {canReply ? (
                        <MessageComposer conversationId={conversation.id} translations={thread} />
                    ) : (
                        <p className="border-t border-rule bg-sand px-5 pt-4 pb-5 text-[13px]/normal text-ink/75 lg:pt-6 lg:pr-10 lg:pb-7 lg:pl-8 lg:text-sm/[1.55] xl:pr-16 xl:pl-12">
                            {thread.read_only}
                        </p>
                    )}
                </section>
            </div>

            <ReportMessageDialog
                open={reportMessage !== null}
                onClose={() => setReportMessage(null)}
                messageId={reportMessage?.id ?? 0}
                preview={reportMessage?.body.slice(0, 100) ?? ''}
                translations={translations.report}
            />
        </PublicLayout>
    )
}
