import { useEffect, useMemo, useState } from 'react'
import type { ChangeEvent } from 'react'
import { ImagePlus, X } from 'lucide-react'
import type { NewListingImagePickerProps } from '@/types/listing'

const MAX_IMAGES = 10

export default function NewListingImagePicker({ files, onChange, errors, translations }: NewListingImagePickerProps) {
    const [inputKey, setInputKey] = useState(0)
    const error = errors.images ?? Object.entries(errors).find(([key]) => key.startsWith('images.'))?.[1]
    const previews = useMemo(() => files.map((file) => URL.createObjectURL(file)), [files])

    useEffect(() => () => previews.forEach((url) => URL.revokeObjectURL(url)), [previews])

    function addFiles(event: ChangeEvent<HTMLInputElement>) {
        const selected = Array.from(event.target.files ?? [])
        onChange([...files, ...selected].slice(0, MAX_IMAGES))
        setInputKey((key) => key + 1)
    }

    function removeFile(index: number) {
        onChange(files.filter((_, fileIndex) => fileIndex !== index))
    }

    return (
        <section>
            <h2 className="font-display text-xl font-semibold text-text">{translations.heading}</h2>
            <p className="mt-1 text-sm text-text-soft">{translations.helper.replace(':max', String(MAX_IMAGES))}</p>

            {files.length > 0 && (
                <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
                    {previews.map((url, index) => (
                        <div
                            key={url}
                            className="relative aspect-4/3 overflow-hidden rounded-lg border border-border bg-surface-muted"
                        >
                            <img src={url} alt={files[index]?.name ?? ''} className="size-full object-cover" />
                            <button
                                type="button"
                                onClick={() => removeFile(index)}
                                aria-label={translations.remove}
                                className="absolute right-2 top-2 flex size-8 items-center justify-center rounded-full bg-white/85 text-status-rejected shadow-sm transition-colors hover:bg-white"
                            >
                                <X className="size-4" />
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <div className="mt-6 flex flex-wrap items-center gap-4">
                {files.length < MAX_IMAGES && (
                    <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-border-strong bg-surface-muted px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-border/40">
                        <ImagePlus className="size-4" />
                        {translations.choose}
                        <input
                            key={inputKey}
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            onChange={addFiles}
                            className="sr-only"
                        />
                    </label>
                )}
                <span className="text-sm text-text-muted">
                    {translations.count.replace(':count', String(files.length)).replace(':max', String(MAX_IMAGES))}
                </span>
            </div>

            {error && <p className="mt-2 text-sm text-status-rejected">{error}</p>}
            <p className="mt-2 text-xs text-text-muted">{translations.format_note}</p>
        </section>
    )
}
