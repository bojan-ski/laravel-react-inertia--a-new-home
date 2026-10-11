import { useState } from 'react'
import type { ChangeEvent, KeyboardEvent } from 'react'
import { router, useForm } from '@inertiajs/react'
import { Loader2 } from 'lucide-react'
import FieldError from '@/Components/Forms/FieldError'
import ListingCoverTag from '@/Components/Listings/ListingCoverTag'
import Button from '@/Components/UI/Button'
import TextField from '@/Components/UI/TextField'
import { useActionLabel } from '@/Components/UI/useActionLabel'
import type { ImageUploaderProps, ImageUploadFormData } from '@/types/listing'

export default function ImageUploader({ baseUrl, images, listingTitle, maxImages, translations }: ImageUploaderProps) {
    const actionLabel = useActionLabel()
    const atLimit = images.length >= maxImages
    const [deletingId, setDeletingId] = useState<number | null>(null)
    const [fileInputKey, setFileInputKey] = useState(0)

    const { data, setData, post, processing, errors, reset } = useForm<ImageUploadFormData>({
        image: null,
        alt_text: '',
    })

    function handleFileChange(event: ChangeEvent<HTMLInputElement>) {
        setData('image', event.target.files?.[0] ?? null)
    }

    function upload() {
        if (data.image === null || processing) {
            return
        }

        post(baseUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset()
                setFileInputKey((key) => key + 1)
            },
        })
    }

    function handleAltKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'Enter') {
            event.preventDefault()
            upload()
        }
    }

    function deleteImage(imageId: number) {
        setDeletingId(imageId)
        router.delete(`${baseUrl}/${imageId}`, {
            preserveScroll: true,
            onFinish: () => setDeletingId(null),
        })
    }

    return (
        <>
            {images.length > 0 && (
                <ul className="col-span-2 grid grid-cols-2 gap-4 md:grid-cols-3">
                    {images.map((image, index) => (
                        <li key={image.id} className="flex min-w-0 flex-col gap-2.5">
                            <div className="relative aspect-4/3 bg-sand">
                                <img
                                    src={image.url}
                                    alt={image.altText ?? listingTitle}
                                    className="absolute inset-0 size-full object-cover"
                                />
                                {index === 0 && <ListingCoverTag label={translations.cover} />}
                            </div>
                            <div className="flex items-center justify-between gap-3 text-[13px]">
                                <span className={`truncate ${image.altText ? '' : 'text-ink-muted'}`}>
                                    {image.altText ?? translations.no_alt}
                                </span>
                                <button
                                    type="button"
                                    onClick={() => deleteImage(image.id)}
                                    disabled={deletingId === image.id}
                                    aria-label={actionLabel(
                                        translations.delete,
                                        image.altText ?? `${translations.image_label} ${index + 1}`,
                                    )}
                                    className="inline-flex shrink-0 items-center gap-1.5 text-ink-muted transition-colors duration-150 hover:text-forest disabled:opacity-45"
                                >
                                    {deletingId === image.id && (
                                        <Loader2 className="size-3.5 animate-spin" aria-hidden="true" />
                                    )}
                                    {translations.delete}
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {atLimit ? (
                <p className="col-span-2 bg-sand px-6 py-5 text-sm">
                    {translations.limit_reached.replace(':max', String(maxImages))}
                </p>
            ) : (
                <div className="@container col-span-2">
                    <div className="flex flex-col gap-4 bg-sand px-6 py-5 @md:grid @md:grid-cols-[minmax(0,1fr)_auto] @md:items-end @md:gap-6 @min-[40rem]:grid-cols-[auto_minmax(0,1fr)_auto]">
                        <div className="flex min-w-0 flex-col gap-1.5 @md:col-span-2 @min-[40rem]:col-span-1">
                            <span className="text-xs text-ink-muted">{translations.image_label}</span>
                            <div className="flex min-h-11 items-center gap-3.5 text-sm">
                                <label className="shrink-0 cursor-pointer border border-ink px-3 py-2 transition-colors duration-150 hover:bg-ink/7 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-forest">
                                    {translations.choose_image}
                                    <input
                                        key={fileInputKey}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handleFileChange}
                                        aria-invalid={errors.image ? true : undefined}
                                        aria-describedby={errors.image ? 'image-upload-error' : undefined}
                                        className="sr-only"
                                    />
                                </label>
                                <span className="min-w-0 truncate text-ink-muted">
                                    {data.image?.name ?? translations.no_file}
                                </span>
                            </div>
                            <FieldError id="image-upload-error" message={errors.image} />
                        </div>

                        <TextField
                            id="image_alt_text"
                            type="text"
                            label={translations.alt_label}
                            placeholder={translations.alt_placeholder}
                            value={data.alt_text}
                            onChange={(event) => setData('alt_text', event.target.value)}
                            onKeyDown={handleAltKeyDown}
                            error={errors.alt_text}
                        />

                        <Button variant="secondary" onClick={upload} disabled={processing || data.image === null}>
                            {processing ? translations.uploading : translations.upload}
                        </Button>
                    </div>
                </div>
            )}

            <p className="col-span-2 text-[13px] text-ink-muted">{translations.format_note}</p>
        </>
    )
}
