import { Head } from '@inertiajs/react'
import ConversationInbox from '@/Components/Conversations/ConversationInbox'
import PublicLayout from '@/Layouts/PublicLayout'
import type { ConversationsIndexPageProps } from '@/types/conversation'

export default function Index({ conversations, translations }: ConversationsIndexPageProps) {
    return (
        <PublicLayout fill="desktop" footer={false}>
            <Head title={translations.title} />

            <div className="grid min-h-0 flex-1 lg:grid-cols-[460px_minmax(0,1fr)] lg:grid-rows-1">
                <ConversationInbox
                    conversations={conversations}
                    translations={translations}
                    className="pb-10 lg:border-r lg:border-rule lg:pb-0"
                />

                <div className="hidden items-center justify-center lg:flex">
                    {conversations.length > 0 && (
                        <p className="text-[15px] text-ink-muted">{translations.select_prompt}</p>
                    )}
                </div>
            </div>
        </PublicLayout>
    )
}
