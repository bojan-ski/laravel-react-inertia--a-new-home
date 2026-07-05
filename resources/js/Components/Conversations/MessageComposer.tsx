import { useForm } from '@inertiajs/react'
import { Send } from 'lucide-react'
import type { FormEvent, KeyboardEvent } from 'react'
import type { MessageComposerProps } from '@/types/conversation'

export default function MessageComposer({ conversationId, translations }: MessageComposerProps) {
    const { data, setData, post, processing, reset } = useForm({ body: '' })

    function submit(event?: FormEvent) {
        event?.preventDefault()

        if (data.body.trim() === '') {
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
        <form onSubmit={submit} className="border-t border-border bg-surface p-4">
            <div className="flex items-end gap-3">
                <textarea
                    value={data.body}
                    onChange={(event) => setData('body', event.target.value)}
                    onKeyDown={handleKeyDown}
                    placeholder={translations.placeholder}
                    rows={1}
                    className="max-h-40 min-h-[44px] flex-1 resize-none rounded-lg border border-border bg-surface px-4 py-2.5 text-sm text-text outline-none transition-colors focus:border-brand"
                />
                <button
                    type="submit"
                    disabled={processing || data.body.trim() === ''}
                    className="flex h-[44px] items-center gap-2 rounded-lg bg-brand px-4 text-sm font-medium text-text-inverse transition-colors hover:bg-brand-dark disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <Send className="size-4" />
                    <span className="hidden sm:inline">{processing ? translations.sending : translations.send}</span>
                </button>
            </div>
            <p className="mt-2 text-xs text-text-muted">{translations.hint}</p>
        </form>
    )
}
