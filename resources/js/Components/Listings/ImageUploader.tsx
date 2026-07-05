import { useState } from 'react'
import type { ChangeEvent, FormEvent } from 'react'
import { router, useForm } from '@inertiajs/react'
import { Loader2, Trash2, Upload } from 'lucide-react'
import type { ImageUploaderProps, ImageUploadFormData } from '@/types/listing'

export default function ImageUploader({ listingId, images, translations, canManage = true }: ImageUploaderProps) {
    const [deletingId, setDeletingId] = useState<number | null>(null)
    const [fileInputKey, setFileInputKey] = useState(0)

    const { data, setData, post, processing, errors, reset } = useForm<ImageUploadFormData>({
        image: null,
        alt_text: '',
    })

    function handleFileChange(event: ChangeEvent<HTMLInputElement>) {
        setData('image', event.target.files?.[0] ?? null)
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()
        post(`/my-listings/${listingId}/images`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset()
                setFileInputKey((key) => key + 1)
            },
        })
    }

    function deleteImage(imageId: number) {
        setDeletingId(imageId)
        router.delete(`/my-listings/${listingId}/images/${imageId}`, {
            preserveScroll: true,
            onFinish: () => setDeletingId(null),
        })
    }

    return (
        <section>
            <h2 className="font-display text-xl font-semibold text-text">{translations.heading}</h2>
            <p className="mt-1 text-sm text-text-soft">
                {images.length === 0 ? translations.helper_empty : translations.helper}
            </p>

            {images.length > 0 && (
                <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    {images.map((image) => (
                        <div
                            key={image.id}
                            className="relative aspect-[4/3] overflow-hidden rounded-lg border border-border bg-surface-muted"
                        >
                            <img src={image.url} alt={image.altText} className="size-full object-cover" />

                            {canManage && (
                                <button
                                    type="button"
                                    onClick={() => deleteImage(image.id)}
                                    disabled={deletingId === image.id}
                                    aria-label={translations.delete}
                                    className="absolute right-2 top-2 flex size-8 items-center justify-center rounded-full bg-white/85 text-status-rejected shadow-sm transition-colors hover:bg-white disabled:opacity-70"
                                >
                                    {deletingId === image.id ? (
                                        <Loader2 className="size-4 animate-spin" />
                                    ) : (
                                        <Trash2 className="size-4" />
                                    )}
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {canManage && (
                <form
                    onSubmit={submit}
                    className="mt-6 flex flex-col gap-4 rounded-xl border border-border bg-surface p-5"
                >
                    <div className="flex flex-col gap-2">
                        <label className="inline-flex w-fit cursor-pointer items-center gap-2 rounded-lg border border-border-strong bg-surface-muted px-4 py-2 text-sm font-medium text-text transition-colors hover:bg-border/40">
                            <Upload className="size-4" />
                            {translations.choose_image}
                            <input
                                key={fileInputKey}
                                type="file"
                                accept="image/*"
                                onChange={handleFileChange}
                                className="sr-only"
                            />
                        </label>
                        <span className="text-sm text-text-muted">{data.image?.name ?? translations.no_file}</span>
                        {errors.image && <p className="text-sm text-status-rejected">{errors.image}</p>}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <label htmlFor="alt_text" className="text-sm font-medium text-text">
                            {translations.alt_label}
                        </label>
                        <input
                            id="alt_text"
                            type="text"
                            value={data.alt_text}
                            onChange={(event) => setData('alt_text', event.target.value)}
                            placeholder={translations.alt_placeholder}
                            className="rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text outline-none focus:border-brand"
                        />
                        {errors.alt_text && <p className="text-sm text-status-rejected">{errors.alt_text}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing || data.image === null}
                        className="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-text-inverse transition-colors hover:bg-brand-dark disabled:opacity-50 sm:w-fit"
                    >
                        {processing && <Loader2 className="size-4 animate-spin" />}
                        {processing ? translations.uploading : translations.upload}
                    </button>

                    <p className="text-xs text-text-muted">{translations.format_note}</p>
                </form>
            )}
        </section>
    )
}
