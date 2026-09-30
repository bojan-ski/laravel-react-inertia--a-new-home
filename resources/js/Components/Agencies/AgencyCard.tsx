import { Link } from '@inertiajs/react'
import LogoTile from '@/Components/UI/LogoTile'
import type { AgencyCardProps } from '@/types/agency'

export default function AgencyCard({ agency, translations }: AgencyCardProps) {
    const propertiesLabel = agency.listingsCount === 1 ? translations.properties_one : translations.properties_other

    return (
        <li className="relative grid grid-cols-[56px_minmax(0,1fr)] gap-3.5 border-t border-rule py-5 transition-colors duration-150 hover:bg-sand/55 lg:grid-cols-[88px_minmax(0,3fr)_minmax(0,4fr)_140px_150px] lg:items-center lg:gap-6 lg:border-t-0 lg:border-b lg:py-7 xl:gap-10">
            <LogoTile name={agency.name} size="row" />

            <div className="flex min-w-0 flex-col gap-1 lg:gap-1.5">
                <h2 className="text-lg wrap-break-word lg:text-[28px] lg:leading-tight lg:font-light lg:tracking-[-0.02em]">
                    <Link
                        href={`/agencies/${agency.slug}`}
                        className="transition-colors duration-150 after:absolute after:inset-0 hover:text-forest"
                    >
                        {agency.name}
                    </Link>
                </h2>

                <p className="flex min-w-0 text-[13px] text-ink-muted lg:hidden">
                    {agency.address && (
                        <>
                            <span className="truncate">{agency.address}</span>
                            <span className="shrink-0 whitespace-pre"> · </span>
                        </>
                    )}
                    <span className="shrink-0">
                        {agency.listingsCount} {propertiesLabel}
                    </span>
                </p>

                {agency.address && <p className="hidden truncate text-sm text-ink-muted lg:block">{agency.address}</p>}

                {agency.description && (
                    <p className="mt-1 line-clamp-3 text-[13px]/normal font-book text-ink/75 lg:hidden">
                        {agency.description}
                    </p>
                )}
            </div>

            <p className="hidden text-[15px]/[1.6] font-book text-ink/75 lg:line-clamp-3">{agency.description}</p>

            <div className="hidden lg:flex lg:flex-col lg:gap-0.5">
                <span className="text-4xl leading-none font-extralight tracking-[-0.02em]">{agency.listingsCount}</span>
                <span className="text-xs tracking-[0.1em] text-ink/60 uppercase">{propertiesLabel}</span>
            </div>

            <span aria-hidden="true" className="hidden text-right text-sm text-forest lg:block">
                {translations.view_agency} →
            </span>
        </li>
    )
}
