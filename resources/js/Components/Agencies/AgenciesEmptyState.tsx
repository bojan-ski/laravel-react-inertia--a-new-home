import { router } from '@inertiajs/react'
import Button from '@/Components/UI/Button'
import EmptyState from '@/Components/UI/EmptyState'
import type { AgenciesEmptyStateProps } from '@/types/agency'

export default function AgenciesEmptyState({ translations, hasActiveSearch }: AgenciesEmptyStateProps) {
    function reset() {
        router.get('/agencies', {}, { preserveState: true, replace: true })
    }

    return (
        <EmptyState
            title={translations.headline}
            hint={hasActiveSearch ? translations.hint : translations.none}
            action={
                hasActiveSearch && (
                    <Button variant="secondary" onClick={reset} className="mt-3">
                        {translations.reset}
                    </Button>
                )
            }
            className="mt-8 lg:mt-2"
        />
    )
}
