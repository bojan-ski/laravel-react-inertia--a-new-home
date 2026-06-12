export interface LandingStat {
    value: string
    label: string
}

export interface LandingFeature {
    title: string
    description: string
}

export interface LandingStep {
    number: string
    title: string
    description: string
}

export interface LandingHero {
    headline_line1: string
    headline_line2: string
    subheading: string
    cta: string
    stats: LandingStat[]
}

export interface LandingFeatures {
    eyebrow: string
    items: LandingFeature[]
}

export interface LandingHowItWorks {
    eyebrow: string
    heading_line1: string
    heading_line2: string
    steps: LandingStep[]
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
    features: LandingFeatures
    how_it_works: LandingHowItWorks
    cta: LandingCta
}

export interface HomePageProps {
    landing: LandingTranslations
}

export interface HeroProps {
    content: LandingHero
}

export interface FeaturesProps {
    content: LandingFeatures
}

export interface FeatureLabelListProps {
    items: LandingFeature[]
    activeIndex: number
}

export interface HowItWorksProps {
    content: LandingHowItWorks
}

export interface ClosingCtaProps {
    content: LandingCta
}
