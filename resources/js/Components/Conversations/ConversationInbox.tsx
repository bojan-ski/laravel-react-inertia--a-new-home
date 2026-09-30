import { usePage } from '@inertiajs/react'
import ConversationRow from '@/Components/Conversations/ConversationRow'
import EmptyState from '@/Components/UI/EmptyState'
import type { ConversationInboxProps } from '@/types/conversation'
import type { SharedProps } from '@/types/shared'

export default function ConversationInbox({
    conversations,
    translations,
    selectedId,
    titleAs: Title = 'h1',
    className = '',
}: ConversationInboxProps) {
    const currentUserId = usePage<SharedProps>().props.auth.user?.id ?? null

    return (
        <section className={`flex min-h-0 flex-col ${className}`}>
            <header className="flex flex-col gap-2 px-5 pt-6 pb-5 md:px-10 lg:pt-10 lg:pb-6 xl:pl-16">
                <Title className="text-[44px] leading-none font-extralight tracking-[-0.04em] lg:text-[56px]">
                    {translations.title}
                </Title>
                <p className="text-sm text-ink-muted">{translations.subtitle}</p>
            </header>

            {conversations.length === 0 ? (
                <EmptyState
                    title={translations.empty.headline}
                    hint={translations.empty.subtitle}
                    className="mx-5 mt-2 md:mx-10 xl:ml-16"
                />
            ) : (
                <ul className="lg:min-h-0 lg:flex-1 lg:overflow-y-auto">
                    {conversations.map((conversation) => (
                        <ConversationRow
                            key={conversation.id}
                            conversation={conversation}
                            translations={translations}
                            currentUserId={currentUserId}
                            selected={conversation.id === selectedId}
                        />
                    ))}
                </ul>
            )}
        </section>
    )
}
