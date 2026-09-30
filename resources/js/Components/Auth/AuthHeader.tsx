import type { AuthHeaderProps } from '@/types/auth'

export default function AuthHeader({ title, subtitle }: AuthHeaderProps) {
    return (
        <div className="flex flex-col gap-3.5">
            <h1 className="text-[44px] leading-[0.95] font-extralight tracking-[-0.045em] lg:text-[72px]">{title}</h1>
            <p className="text-[15px] leading-[1.55] font-book text-ink-muted lg:text-[17px]">{subtitle}</p>
        </div>
    )
}
