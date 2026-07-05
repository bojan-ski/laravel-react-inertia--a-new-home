import { Head } from '@inertiajs/react'
import { MessageCircle } from 'lucide-react'
import ConversationRow from '@/Components/Conversations/ConversationRow'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ConversationsIndexPageProps } from '@/types/conversation'

export default function Index({ conversations, translations }: ConversationsIndexPageProps) {
    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-4xl px-6 py-10 md:py-14">
                    <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">{translations.title}</h1>
                    <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                </div>
            </div>

            <div className="mx-auto max-w-4xl px-6 py-8">
                {conversations.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-3 py-24 text-center">
                        <MessageCircle className="size-10 text-text-muted" />
                        <p className="text-lg text-text-soft">{translations.empty.headline}</p>
                        <p className="text-sm text-text-muted">{translations.empty.subtitle}</p>
                    </div>
                ) : (
                    <div className="flex flex-col gap-3">
                        {conversations.map((conversation) => (
                            <ConversationRow
                                key={conversation.id}
                                conversation={conversation}
                                translations={translations}
                            />
                        ))}
                    </div>
                )}
            </div>
        </PublicLayout>
    )
}
