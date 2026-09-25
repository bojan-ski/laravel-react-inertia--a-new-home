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

export interface StaticPageProps {
    page: StaticPageContent
}
