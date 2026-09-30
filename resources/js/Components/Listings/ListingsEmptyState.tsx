import { router } from '@inertiajs/react'
import Button from '@/Components/UI/Button'
import EmptyState from '@/Components/UI/EmptyState'
import type { ListingsEmptyStateProps } from '@/types/listing'

export default function ListingsEmptyState({ translations, hasActiveFilters }: ListingsEmptyStateProps) {
    function reset() {
        router.get('/listings', {}, { preserveScroll: true, replace: true })
    }

    return (
        <EmptyState
            title={translations.headline}
            hint={hasActiveFilters ? translations.hint : undefined}
            action={
                hasActiveFilters && (
                    <Button variant="secondary" size="sm" onClick={reset} className="mt-3">
                        {translations.reset}
                    </Button>
                )
            }
            className="mt-10"
        />
    )
}
