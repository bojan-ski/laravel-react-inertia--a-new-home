import { usePage } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'
import Button from '@/Components/UI/Button'
import type { ClosingCtaProps } from '@/types/home'
import type { SharedProps, UserRole } from '@/types/shared'

const listPropertyPaths: Record<UserRole, string | null> = {
    regular_user: '/my-listings/create',
    agency: '/agency/listings/create',
    admin: null,
}

export default function ClosingCta({ content }: ClosingCtaProps) {
    const user = usePage<SharedProps>().props.auth?.user ?? null
    const listPropertyHref = user ? listPropertyPaths[user.role] : '/register'

    return (
        <section className="bg-sand">
            <div className="mx-auto flex max-w-360 flex-col gap-5 px-5 py-18 md:px-10 lg:grid lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end lg:gap-16 lg:px-16 lg:py-30">
                <h2 className="text-[44px] leading-none font-extralight tracking-[-0.04em] lg:text-[80px]">{content.heading}</h2>

                <div className="flex flex-col gap-7">
                    <p className="text-base leading-[1.6] font-book lg:text-[17px]">{content.paragraph}</p>

                    <div className="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:gap-3">
                        <Button
                            href="/listings"
                            size="lg"
                            className="whitespace-nowrap max-sm:justify-between sm:gap-10"
                            trailing={<ArrowRight className="size-5" strokeWidth={1.25} aria-hidden="true" />}
                        >
                            {content.browse}
                        </Button>
                        {listPropertyHref && (
                            <Button href={listPropertyHref} variant="secondary" size="lg" className="whitespace-nowrap max-sm:justify-start">
                                {content.list_property}
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </section>
    )
}
