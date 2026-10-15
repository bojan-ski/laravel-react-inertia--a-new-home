import useReveal from '@/Components/UI/useReveal'

export default function PhotoPair() {
    const reveal = useReveal<HTMLElement>('sides', null)

    return (
        <section
            {...reveal}
            className="mx-auto flex max-w-360 flex-col gap-4 overflow-x-clip px-5 pb-18 md:px-10 lg:grid lg:grid-cols-12 lg:gap-8 lg:px-16 lg:pb-40"
        >
            <img
                src="/images/family-house-white-brown.webp"
                alt=""
                width={1200}
                height={900}
                loading="lazy"
                decoding="async"
                className="aspect-4/3 w-full object-cover lg:col-span-7"
            />
            <img
                src="/images/stone-house-hillside.webp"
                alt=""
                width={1200}
                height={1389}
                loading="lazy"
                decoding="async"
                className="aspect-4/5 w-[68%] self-end object-cover lg:col-span-5 lg:w-full"
            />
        </section>
    )
}
