import type { FormSectionLink } from '@/types/form'

export type StaticPageKey = 'about' | 'privacy' | 'terms' | 'cookies'

export interface StaticPageSection {
    heading: string
    paragraphs: string[]
}

export interface StaticPageContent {
    eyebrow: string
    title: string
    lead: string
    updated: string | null
    sections: StaticPageSection[]
}

export interface StaticPageTranslations {
    index_label: string
}

export interface StaticPageProps {
    pageKey: StaticPageKey
    page: StaticPageContent
    translations: StaticPageTranslations
}

export interface StaticSectionIndexProps {
    sections: FormSectionLink[]
    label: string
}
