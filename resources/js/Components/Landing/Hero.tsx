import { router } from '@inertiajs/react'
import { ArrowRight } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import SegmentedToggle from '@/Components/UI/SegmentedToggle'
import type { HeroProps } from '@/types/home'

type HeroListingType = 'sale' | 'rent'

export default function Hero({ content }: HeroProps) {
    const [listingType, setListingType] = useState<HeroListingType>('sale')
    const [search, setSearch] = useState('')

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()

        const term = search.trim()

        router.get('/listings', { listingType, ...(term ? { search: term } : {}) })
    }

    return (
        <section className="relative h-175 overflow-hidden text-paper lg:h-220">
            <img
                src="/images/hero-modern-house-white.webp"
                alt=""
                width={2000}
                height={1327}
                fetchPriority="high"
                className="absolute inset-0 size-full object-cover"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[linear-gradient(to_bottom,rgba(12,20,15,0.35),rgba(12,20,15,0)_20%,rgba(12,20,15,0)_40%,rgba(12,20,15,0.6))] lg:bg-[linear-gradient(to_bottom,rgba(12,20,15,0.35),rgba(12,20,15,0)_22%,rgba(12,20,15,0)_50%,rgba(12,20,15,0.55))]"
            />

            <div className="absolute inset-x-0 bottom-12 lg:bottom-18">
                <div className="mx-auto flex max-w-360 flex-col gap-7 px-5 md:px-10 lg:grid lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-end lg:gap-16 lg:px-16">
                    <h1 className="text-[54px] leading-[0.96] font-extralight tracking-[-0.045em] md:text-[80px] xl:text-display-xl xl:leading-[0.95]">
                        {content.headline}
                    </h1>

                    <form role="search" onSubmit={submit} className="flex flex-col gap-3 lg:gap-5">
                        <SegmentedToggle
                            name="listingType"
                            legend={content.listing_type_label}
                            options={[
                                { value: 'sale', label: content.buy },
                                { value: 'rent', label: content.rent },
                            ]}
                            value={listingType}
                            onChange={setListingType}
                            size="sm"
                            tone="on-photo"
                            className="tracking-[0.03em]"
                        />

                        <div className="flex min-h-12 items-center justify-between gap-3 border-b border-paper/80 focus-within:border-paper focus-within:outline-2 focus-within:outline-offset-4 focus-within:outline-paper lg:py-1">
                            <label htmlFor="hero-search" className="sr-only">
                                {content.search_label}
                            </label>
                            <input
                                id="hero-search"
                                type="search"
                                name="search"
                                maxLength={255}
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={content.search_placeholder}
                                className="w-full min-w-0 appearance-none bg-transparent text-[17px] font-light text-paper placeholder:text-paper/70 focus-visible:outline-none lg:text-xl [&::-webkit-search-cancel-button]:hidden"
                            />
                            <button
                                type="submit"
                                aria-label={content.search_submit}
                                className="flex size-11 shrink-0 items-center justify-end text-paper transition-colors duration-150 hover:text-forest-200 focus-visible:outline-paper"
                            >
                                <ArrowRight className="size-5.5" strokeWidth={1} aria-hidden="true" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    )
}
