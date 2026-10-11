import type { SyntheticEvent } from 'react'

function markLoaded(event: SyntheticEvent<HTMLImageElement>) {
    event.currentTarget.setAttribute('data-loaded', '')
}

function markIfComplete(image: HTMLImageElement | null) {
    if (image?.complete) {
        image.setAttribute('data-loaded', '')
    }
}

export const photoFadeProps = {
    'data-photo-fade': '',
    ref: markIfComplete,
    onLoad: markLoaded,
    onError: markLoaded,
}
