import { useState } from 'react'
import { Head, router, usePage } from '@inertiajs/react'
import Button from '@/Components/UI/Button'
import Kicker from '@/Components/UI/Kicker'
import PublicLayout from '@/Layouts/PublicLayout'
import type { SavedSearchUnsubscribePageProps } from '@/types/saved-search'
import type { SharedProps } from '@/types/shared'

export default function Unsubscribe({ confirmUrl, translations }: SavedSearchUnsubscribePageProps) {
    const { flash } = usePage<SharedProps>().props
    const [processing, setProcessing] = useState(false)
    const isDone = Boolean(flash.success)

    function confirm() {
        router.post(confirmUrl, {}, {
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        })
    }

    return (
        <PublicLayout>
            <Head title={isDone ? translations.done : translations.title} />

            <div className="mx-auto flex max-w-110 flex-col items-start gap-5.5 px-5 py-20 lg:py-32">
                <Kicker as="p">{translations.eyebrow}</Kicker>

                <h1 className="text-[34px] leading-tight font-airy tracking-[-0.03em]">
                    {isDone ? translations.done : translations.title}
                </h1>

                <p className="text-sm leading-[1.55] text-ink-muted">{isDone ? translations.done_lead : translations.lead}</p>

                {isDone ? (
                    <Button href="/saved-searches" variant="secondary">
                        {translations.manage}
                    </Button>
                ) : (
                    <Button onClick={confirm} disabled={processing}>
                        {processing ? translations.processing : translations.confirm}
                    </Button>
                )}
            </div>
        </PublicLayout>
    )
}
