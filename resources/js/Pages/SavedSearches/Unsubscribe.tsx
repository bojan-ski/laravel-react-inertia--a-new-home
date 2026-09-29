import { useState } from 'react'
import { Head, Link, router, usePage } from '@inertiajs/react'
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

            <div className="mx-auto max-w-2xl px-6 py-20 md:py-28">
                <p className="text-xs font-medium uppercase tracking-wider text-accent">{translations.eyebrow}</p>

                {isDone ? (
                    <>
                        <h1 className="mt-3 font-display text-4xl font-semibold text-text">{translations.done}</h1>
                        <p className="mt-4 text-lg leading-relaxed text-text-soft">{translations.done_lead}</p>
                        <Link
                            href="/saved-searches"
                            className="mt-8 inline-block rounded-full bg-brand px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark"
                        >
                            {translations.manage}
                        </Link>
                    </>
                ) : (
                    <>
                        <h1 className="mt-3 font-display text-4xl font-semibold text-text">{translations.title}</h1>
                        <p className="mt-4 text-lg leading-relaxed text-text-soft">{translations.lead}</p>
                        <button
                            type="button"
                            onClick={confirm}
                            disabled={processing}
                            className="mt-8 rounded-full bg-surface-dark px-6 py-3 text-sm font-semibold text-text-inverse transition-colors hover:bg-text disabled:opacity-50"
                        >
                            {processing ? translations.processing : translations.confirm}
                        </button>
                    </>
                )}
            </div>
        </PublicLayout>
    )
}
