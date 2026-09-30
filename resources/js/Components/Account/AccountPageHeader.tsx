import AccountTabs from '@/Components/Account/AccountTabs'
import type { AccountPageHeaderProps } from '@/types/navigation'

export default function AccountPageHeader({ title, subtitle, action, mobileAction }: AccountPageHeaderProps) {
    return (
        <header className="px-5 pt-6 md:px-10 lg:pt-8 xl:px-16">
            <div className="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between lg:gap-8">
                <div className="flex flex-col gap-2 lg:gap-3">
                    <h1 className="text-[44px] leading-none font-extralight tracking-[-0.04em] wrap-break-word lg:text-[88px] lg:leading-[0.95] lg:tracking-[-0.045em]">
                        {title}
                    </h1>
                    <p className="text-sm leading-normal font-book text-ink-muted lg:text-[17px]">{subtitle}</p>
                </div>

                {action && <div className="hidden shrink-0 lg:block">{action}</div>}
                {mobileAction && <div className="lg:hidden">{mobileAction}</div>}
            </div>

            <div className="mt-5 lg:mt-10">
                <AccountTabs />
            </div>
        </header>
    )
}
