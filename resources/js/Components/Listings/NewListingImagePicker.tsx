import { useEffect, useMemo, useState } from 'react'
import type { ChangeEvent } from 'react'
import { X } from 'lucide-react'
import FieldError from '@/Components/Forms/FieldError'
import ListingCoverTag from '@/Components/Listings/ListingCoverTag'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { NewListingImagePickerProps } from '@/types/listing'

export default function NewListingImagePicker({
    files,
    onChange,
    errors,
    maxImages,
    translations,
}: NewListingImagePickerProps) {
    const [inputKey, setInputKey] = useState(0)
    const actionLabel = useActionLabel()
    const error = errors.images ?? Object.entries(errors).find(([key]) => key.startsWith('images.'))?.[1]
    const previews = useMemo(() => files.map((file) => URL.createObjectURL(file)), [files])

    useEffect(() => () => previews.forEach((url) => URL.revokeObjectURL(url)), [previews])

    function addFiles(event: ChangeEvent<HTMLInputElement>) {
        const selected = Array.from(event.target.files ?? [])
        onChange([...files, ...selected].slice(0, maxImages))
        setInputKey((key) => key + 1)
    }

    function removeFile(index: number) {
        onChange(files.filter((_, fileIndex) => fileIndex !== index))
    }

    return (
        <>
            <div className="col-span-2 grid grid-cols-3 gap-2 lg:grid-cols-5 lg:gap-3">
                {previews.map((url, index) => (
                    <div key={url} className="relative aspect-4/3 bg-sand">
                        <img
                            src={url}
                            alt={files[index]?.name ?? ''}
                            className="absolute inset-0 size-full object-cover"
                        />
                        {index === 0 && <ListingCoverTag label={translations.cover} />}
                        <button
                            type="button"
                            onClick={() => removeFile(index)}
                            aria-label={actionLabel(translations.remove, files[index]?.name ?? String(index + 1))}
                            className="absolute top-0 right-0 flex size-8 items-center justify-center bg-paper text-ink transition-colors duration-150 hover:bg-sand"
                        >
                            <X className="size-3.5" strokeWidth={1.5} aria-hidden="true" />
                        </button>
                    </div>
                ))}

                {files.length < maxImages && (
                    <label className="flex aspect-4/3 cursor-pointer flex-col items-start justify-end gap-1 border border-rule p-3 transition-colors duration-150 hover:bg-ink/4 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-forest">
                        <span aria-hidden="true" className="text-[22px] leading-none font-extralight text-forest">
                            +
                        </span>
                        <span className="text-[13px]">{translations.choose}</span>
                        <input
                            key={inputKey}
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            onChange={addFiles}
                            aria-invalid={error ? true : undefined}
                            aria-describedby={error ? 'new-images-error' : undefined}
                            className="sr-only"
                        />
                    </label>
                )}
            </div>

            <div className="col-span-2 flex flex-col gap-2">
                <div className="flex justify-between gap-4 text-[13px] text-ink-muted">
                    <span>
                        {translations.count.replace(':count', String(files.length)).replace(':max', String(maxImages))}
                    </span>
                    <span className="text-right">{translations.format_note}</span>
                </div>
                <FieldError id="new-images-error" message={error} />
            </div>
        </>
    )
}
