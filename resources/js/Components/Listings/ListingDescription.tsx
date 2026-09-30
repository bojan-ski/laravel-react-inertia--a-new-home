import type { ListingDescriptionProps } from '@/types/listing'

const LEAD_MAX_LENGTH = 280

function splitDescription(description: string): { lead: string; body: string } {
    const text = description.trim()
    const paragraphBreak = /\n\s*\n/.exec(text)

    if (paragraphBreak) {
        return {
            lead: text.slice(0, paragraphBreak.index).trim(),
            body: text.slice(paragraphBreak.index + paragraphBreak[0].length).trim(),
        }
    }

    return text.length <= LEAD_MAX_LENGTH ? { lead: text, body: '' } : { lead: '', body: text }
}

export default function ListingDescription({ description }: ListingDescriptionProps) {
    const { lead, body } = splitDescription(description)

    return (
        <div className="flex flex-col gap-3.5 @4xl:gap-5">
            {lead && (
                <p className="text-[21px]/[1.4] font-airy @4xl:text-[28px]/[1.35] @4xl:tracking-[-0.01em]">{lead}</p>
            )}
            {body && (
                <p className="text-[15px]/[1.7] font-book whitespace-pre-line text-ink/80 @4xl:text-base/[1.75]">
                    {body}
                </p>
            )}
        </div>
    )
}
