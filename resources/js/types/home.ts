export interface LandingStat {
    value: string
    label: string
}

export interface LandingFeature {
    title: string
    description: string
}

export interface LandingHero {
    headline: string
    listing_type_label: string
    buy: string
    rent: string
    search_label: string
    search_placeholder: string
    search_submit: string
}

export interface LandingIntro {
    kicker: string
    lead: string
    stats: LandingStat[]
}

export interface LandingFeatures {
    items: LandingFeature[]
}

export interface LandingLatest {
    heading: string
    link: string
    per_month: string
}

export interface LandingCta {
    heading: string
    paragraph: string
    browse: string
    list_property: string
}

export interface LandingTranslations {
    title: string
    hero: LandingHero
    intro: LandingIntro
    features: LandingFeatures
    latest: LandingLatest
    cta: LandingCta
}

export interface LatestListingData {
    id: number
    title: string
    slug: string
    listingType: string
    price: string
    currency: string
    city: string
    country: string
    imageUrl: string | null
}

export interface HomePageProps {
    landing: LandingTranslations
    latestListings: LatestListingData[]
}

export interface HeroProps {
    content: LandingHero
}

export interface IntroProps {
    content: LandingIntro
}

export interface FeaturesProps {
    content: LandingFeatures
}

export interface LatestHomesProps {
    content: LandingLatest
    listings: LatestListingData[]
}

export interface ClosingCtaProps {
    content: LandingCta
}
