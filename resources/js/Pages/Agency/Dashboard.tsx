import { Head } from '@inertiajs/react'
import { Info } from 'lucide-react'
import PublicLayout from '@/Layouts/PublicLayout'
import type { AgencyDashboardPageProps } from '@/types/agency'

export default function Dashboard({ agency, translations }: AgencyDashboardPageProps) {
    return (
        <PublicLayout>
            <Head title={translations.title} />

            <div className="border-b border-border bg-surface">
                <div className="mx-auto max-w-7xl px-6 py-10 md:py-14">
                    <h1 className="font-display text-3xl font-semibold text-text sm:text-4xl">{agency.name}</h1>
                    <p className="mt-2 text-text-soft">{translations.subtitle}</p>
                </div>
            </div>

            <div className="mx-auto max-w-2xl px-6 py-10">
                <div className="flex items-start gap-3 rounded-lg border border-accent bg-accent-soft px-4 py-3">
                    <Info className="mt-0.5 size-5 shrink-0 text-accent" />
                    <p className="text-sm text-text">{translations.pending_notice}</p>
                </div>
            </div>
        </PublicLayout>
    )
}
