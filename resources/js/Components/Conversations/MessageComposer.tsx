import { useForm } from '@inertiajs/react'
import type { FormEvent, KeyboardEvent } from 'react'
import Button from '@/Components/UI/Button'
import type { MessageComposerProps } from '@/types/conversation'

export default function MessageComposer({ conversationId, translations }: MessageComposerProps) {
    const { data, setData, post, processing, reset } = useForm({ body: '' })
    const isBlank = data.body.trim() === ''

    function submit(event?: FormEvent) {
        event?.preventDefault()

        if (isBlank) {
            return
        }

        post(`/conversations/${conversationId}/messages`, {
            preserveScroll: true,
            onSuccess: () => reset('body'),
        })
    }

    function handleKeyDown(event: KeyboardEvent<HTMLTextAreaElement>) {
        if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
            event.preventDefault()
            submit()
        }
    }

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-2.5 border-t border-rule pt-2.5 pr-3 pb-3.5 pl-5 lg:pt-5 lg:pr-10 lg:pb-6 lg:pl-8 xl:pr-16 xl:pl-12"
        >
            <div className="grid grid-cols-[minmax(0,1fr)_52px] items-end gap-2 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-4">
                <textarea
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder={translations.placeholder}
                    aria-label={translations.placeholder}
                    rows={2}
                    className="h-12 resize-none bg-transparent py-3 text-base/normal font-book text-ink transition-colors duration-150 placeholder:text-ink-faint lg:h-auto lg:border-b lg:border-ink lg:py-2 lg:focus:border-forest lg:focus-visible:outline-none"
                />

                <div className="lg:hidden">
                    <Button
                        type="submit"
                        disabled={processing || isBlank}
                        aria-label={translations.send}
                        className="h-13! w-13! px-0!"
                    >
                        <span aria-hidden="true">→</span>
                    </Button>
                </div>

                <div className="hidden lg:block">
                    <Button
                        type="submit"
                        size="md"
                        disabled={processing || isBlank}
                        trailing={<span aria-hidden="true">→</span>}
                    >
                        {processing ? translations.sending : translations.send}
                    </Button>
                </div>
            </div>

            <p className="hidden text-xs text-ink/55 lg:block">{translations.hint}</p>
        </form>
    )
}
