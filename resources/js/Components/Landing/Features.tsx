import { BadgeCheck, Heart, MessageCircle, Search } from 'lucide-react'
import type { FeaturesProps } from '@/types/home'

const icons = [Search, BadgeCheck, MessageCircle, Heart]

export default function Features({ content }: FeaturesProps) {
    return (
        <section className="mx-auto grid max-w-360 grid-cols-1 gap-9 px-5 pb-18 md:grid-cols-2 md:gap-x-12 md:px-10 lg:grid-cols-4 lg:gap-12 lg:px-16 lg:pb-40">
            {content.items.map((item, index) => {
                const Icon = icons[index] ?? Search

                return (
                    <div key={item.title} className="grid grid-cols-[40px_minmax(0,1fr)] gap-4 lg:flex lg:flex-col lg:gap-3.5">
                        <Icon className="size-7 text-forest lg:size-8" strokeWidth={1} aria-hidden="true" />
                        <div className="flex flex-col gap-1.5 lg:gap-3.5">
                            <h2 className="text-lg font-medium lg:mt-3 lg:text-[19px]">{item.title}</h2>
                            <p className="text-[15px] leading-[1.55] font-book text-ink/72 lg:leading-[1.6]">
                                {item.description}
                            </p>
                        </div>
                    </div>
                )
            })}
        </section>
    )
}
