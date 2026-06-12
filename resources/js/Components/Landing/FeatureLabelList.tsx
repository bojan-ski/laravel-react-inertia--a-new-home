import type { FeatureLabelListProps } from '@/types/home'

export default function FeatureLabelList({ items, activeIndex }: FeatureLabelListProps) {
    return (
        <div className="flex flex-col gap-8">
            {items.map((item, index) => {
                const isActive = index === activeIndex

                return (
                    <div key={item.title}>
                        <h3
                            className={`font-display text-3xl transition-opacity duration-500 motion-reduce:transition-none sm:text-4xl md:text-5xl ${
                                isActive ? 'text-white' : 'text-white/30'
                            }`}
                        >
                            {item.title}
                        </h3>

                        {isActive && (
                            <p className="mt-3 max-w-md text-base leading-relaxed text-white/70">
                                {item.description}
                            </p>
                        )}
                    </div>
                )
            })}
        </div>
    )
}
