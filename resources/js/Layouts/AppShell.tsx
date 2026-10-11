import { usePage } from '@inertiajs/react'
import MessageNotifier from '@/Components/Conversations/MessageNotifier'
import CookieBanner from '@/Components/CookieBanner'
import FlashToast from '@/Components/UI/FlashToast'
import type { AppShellProps } from '@/types/layout'
import type { SharedProps } from '@/types/shared'

export default function AppShell({ children }: AppShellProps) {
    const user = usePage<SharedProps>().props.auth?.user ?? null

    return (
        <>
            {children}
            <FlashToast />
            <CookieBanner />
            {user && <MessageNotifier key={user.id} userId={user.id} />}
        </>
    )
}
