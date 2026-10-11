import CountUp from '@/Components/UI/CountUp'
import Kicker from '@/Components/UI/Kicker'
import type { IntroProps } from '@/types/home'

export default function Intro({ content }: IntroProps) {
    return (
        <section className="mx-auto max-w-360 px-5 pt-18 pb-16 md:px-10 lg:grid lg:grid-cols-[minmax(0,4fr)_minmax(0,8fr)] lg:gap-16 lg:px-16 lg:pt-40 lg:pb-30">
            <Kicker as="p" className="lg:pt-4">
                {content.kicker}
            </Kicker>

            <div className="mt-6 flex flex-col gap-10 lg:mt-0 lg:gap-18">
                <p className="text-[28px] leading-[1.25] font-airy tracking-[-0.015em] lg:text-[44px] lg:leading-[1.22] lg:tracking-[-0.02em]">
                    {content.lead}
                </p>

                <dl className="grid grid-cols-3 gap-4 lg:gap-8">
                    {content.stats.map((stat) => (
                        <div key={stat.label} className="flex flex-col-reverse justify-end gap-1 border-t border-rule pt-3.5 lg:gap-1.5 lg:pt-5">
                            <dt className="text-[11px] tracking-[0.1em] text-ink-muted uppercase lg:text-[13px]">
                                {stat.label}
                            </dt>
                            <dd className="text-[34px] leading-[normal] font-extralight tracking-[-0.03em] lg:text-[56px]">
                                <CountUp value={stat.value} />
                            </dd>
                        </div>
                    ))}
                </dl>
            </div>
        </section>
    )
}
