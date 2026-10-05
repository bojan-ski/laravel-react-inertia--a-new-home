<?php

namespace Database\Seeders;

use App\Enums\AgencyStatus;
use App\Enums\Amenity;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\Bookmark;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use App\Services\AgencyService;
use App\Services\ContactMessageService;
use App\Services\ConversationService;
use App\Services\ListingImageService;
use App\Services\ListingReportService;
use App\Services\ReportedChatMessageService;
use Database\Factories\ListingFactory;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class ReviewScenarioSeeder extends Seeder
{
    public const APPROVED_LISTINGS = 59;

    public const PUBLIC_LISTINGS = 55;

    public const PENDING_LISTINGS = 4;

    public const REJECTED_LISTINGS = 2;

    public const CLOSED_LISTINGS = 2;

    public const REGULAR_USERS = 15;

    public const AGENCY_USERS = 19;

    public const APPROVED_AGENCIES = 16;

    public const DIRECTORY_AGENCIES = 15;

    public const NO_PHOTO_TITLE = 'Plain Loft in Graz';

    private const PHOTOS = [
        'hero-modern-house-white.webp',
        'family-house-white-brown.webp',
        'stone-house-hillside.webp',
        'whitewashed-house-timber-doors.webp',
        'family-house-white-brown-portrait.webp',
    ];

    private const LOCATIONS = [
        ['Slovenia', 'Ljubljana'],
        ['Croatia', 'Zagreb'],
        ['Austria', 'Vienna'],
        ['Germany', 'Berlin'],
        ['Serbia', 'Belgrade'],
        ['Italy', 'Trieste'],
        ['Slovenia', 'Maribor'],
        ['Croatia', 'Split'],
        ['Austria', 'Graz'],
        ['Germany', 'Munich'],
        ['Serbia', 'Novi Sad'],
        ['Netherlands', 'Utrecht'],
    ];

    private const FILLER_TYPES = ['flat', 'apartment', 'villa', 'condo', 'land', 'commercial', 'other', 'cottage'];

    private const TYPE_NOUNS = [
        'house' => 'House',
        'flat' => 'Flat',
        'apartment' => 'Apartment',
        'villa' => 'Villa',
        'condo' => 'Condo',
        'land' => 'Building Plot',
        'commercial' => 'Shopfront',
        'other' => 'Loft',
        'cottage' => 'Cottage',
    ];

    private const ADJECTIVES = ['Bright', 'Quiet', 'Spacious', 'Renovated', 'Sunny', 'Classic', 'Modern', 'Cosy', 'Elegant', 'Airy'];

    private const STREETS = ['Linden Street', 'Harbour Road', 'Mill Lane', 'Castle Hill', 'River Walk', 'Station Square', 'Orchard Row', 'Market Street'];

    private const AMENITY_SETS = [
        [],
        [Amenity::Parking],
        [Amenity::Elevator, Amenity::Balcony],
        [Amenity::Garden, Amenity::Parking, Amenity::Storage],
        [Amenity::Terrace, Amenity::AirConditioning],
        [Amenity::CentralHeating, Amenity::Furnished, Amenity::PetsAllowed],
        [Amenity::Pool, Amenity::Garden, Amenity::Security, Amenity::Garage],
    ];

    private const FILLER_AGENCIES = [
        ['Alpine Gate Realty', 'Alma Gates'],
        ['Baltic Light Homes', 'Bruno Light'],
        ['Cedar Row Estates', 'Cora Rowe'],
        ['Danube Bend Properties', 'Dario Bend'],
        ['Elm Court Realty', 'Elsa Court'],
        ['Fjord View Homes', 'Frida View'],
        ['Granite Bay Estates', 'Gustav Bay'],
        ['Hazel Park Properties', 'Hana Park'],
        ['Iris Hill Realty', 'Ivo Hill'],
        ['Juniper Lane Homes', 'Jana Lane'],
        ['Kestrel Point Estates', 'Karl Point'],
        ['Linden Square Properties', 'Lara Square'],
    ];

    private const BIG_AGENCY_LISTINGS = 13;

    private int $listingIndex = 0;

    private int $photoIndex = 0;

    private int $agencyIndex = 0;

    public function __construct(
        private ListingImageService $images,
        private AgencyService $agencies,
        private ConversationService $conversations,
        private ListingReportService $listingReports,
        private ReportedChatMessageService $messageReports,
        private ContactMessageService $contactMessages,
    ) {}

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('ReviewScenarioSeeder only runs in the local and testing environments.');
        }

        $admin = $this->account('admin@newbeginning.test', 'Admin', UserRole::Admin);
        $seller = $this->account('seller@newbeginning.test', 'Sara Sellers');
        $buyer = $this->account('buyer@newbeginning.test', 'Ben Buyer');
        $this->account('empty@newbeginning.test', 'Emma Empty');
        $collector = $this->account('collector@newbeginning.test', 'Colin Collector');
        $this->account('reset@newbeginning.test', 'Rita Reset');
        $offender = $this->account('offender@newbeginning.test', 'Oscar Offender');
        $cascade = $this->account('cascade@newbeginning.test', 'Clara Cascade');
        $this->account('suspended@newbeginning.test', 'Sam Suspended', suspended: true);
        $leaver = $this->account('leaver@newbeginning.test', 'Leo Leaver');
        $this->account('removed@newbeginning.test', 'Rosa Removed');
        $quietOwner = $this->account('quiet-owner@newbeginning.test', 'Quinn Owner');
        $noisyBuyer = $this->account('noisy-buyer@newbeginning.test', 'Ned Noisy');
        $reporterOne = $this->account('reporter-one@newbeginning.test', 'Rhea Reporter');
        $reporterTwo = $this->account('reporter-two@newbeginning.test', 'Ron Reporter');
        $reporterThree = $this->account('reporter-three@newbeginning.test', 'Ruth Reporter');

        $harbourHill = $this->agency('agency@newbeginning.test', 'Anna Agency', 'Harbour Hill Estates', AgencyStatus::Approved);
        $this->agency('agency-pending@newbeginning.test', 'Paul Pending', 'Pending Point Realty', AgencyStatus::PendingVerification);
        $this->agency('agency-rejectme@newbeginning.test', 'Rachel Reject', 'Reject Row Homes', AgencyStatus::PendingVerification);
        $changeLane = $this->agency('agency-change@newbeginning.test', 'Chris Change', 'Change Lane Properties', AgencyStatus::Approved);
        $leavingLight = $this->agency('agency-leaver@newbeginning.test', 'Lena Leaver', 'Leaving Light Realty', AgencyStatus::Approved);
        $quietStreet = $this->agency('agency-suspended@newbeginning.test', 'Stan Suspended', 'Quiet Street Homes', AgencyStatus::Approved, suspended: true);
        $this->agency('agency-hidden@newbeginning.test', 'Hugo Hidden', 'Hidden Hollow Estates', AgencyStatus::Hidden);

        $fillerAgencies = collect(self::FILLER_AGENCIES)->map(fn (array $names, int $position): Agency => $this->agency(
            sprintf('agency-filler-%02d@newbeginning.test', $position + 1),
            $names[1],
            $names[0],
            AgencyStatus::Approved,
        ));

        $sellerListings = $this->seedSellerListings($seller);
        $this->seedAgencyListings($harbourHill);

        $quietListings = collect(range(1, 6))->map(fn (): Listing => $this->filler($quietOwner));
        $this->seedAnchorNeighbours($quietOwner);
        $this->listing(Listing::factory()->approved(), $quietOwner, [
            ...$this->lifetime(3),
            'title' => self::NO_PHOTO_TITLE,
            'listing_type' => 'sale',
            'property_type' => 'flat',
            'country' => 'Austria',
            'city' => 'Graz',
            'price' => 129_000,
        ], 0);

        $fillerAgencies->each(function (Agency $agency, int $position): void {
            $count = $position === count(self::FILLER_AGENCIES) - 1 ? self::BIG_AGENCY_LISTINGS : 1;

            for ($listing = 0; $listing < $count; $listing++) {
                $this->filler($agency);
            }
        });

        $cascadeListings = collect(range(1, 3))->map(fn (): Listing => $this->filler($cascade));
        $leaverListing = $this->filler($leaver);
        $this->filler($leavingLight);
        $this->filler($changeLane);
        $this->filler($quietStreet);
        $this->filler($quietStreet);
        $buyerListing = $this->filler($buyer);

        $this->agencies->submitChanges($changeLane, [
            ...collect(Agency::PROFILE_FIELDS)->mapWithKeys(fn (string $field): array => [$field => $changeLane->profileValue($field)])->all(),
            'phone' => '+386 1 555 0999',
            'description' => 'Change Lane Properties now also manages holiday lets on the coast.',
        ]);

        foreach ([$sellerListings['S-ANCHOR'], $sellerListings['S-SOLD'], $sellerListings['S-EXPIRED-B'], $cascadeListings->first()] as $listing) {
            Bookmark::factory()->create(['user_id' => $buyer->id, 'listing_id' => $listing->id]);
        }

        $this->seedConversations($buyer, $seller, $offender, $leaver, $sellerListings['S-FRESH'], $buyerListing, $leaverListing);
        $this->seedMessageReport($quietOwner, $noisyBuyer, $quietListings->first());
        $this->seedListingReports($admin, $reporterOne, $reporterTwo, $reporterThree, $sellerListings, $quietListings->get(1));
        $this->seedSavedSearches($buyer, $collector);
        $this->seedContactMessages($admin, $buyer);
    }

    /**
     * @return array<string, Listing>
     */
    private function seedSellerListings(User $seller): array
    {
        return [
            'S-SALE' => $this->listing(Listing::factory()->approved(), $seller, [
                ...$this->lifetime(10),
                ...$this->keyed('S-SALE', 'Sunny Flat in Ljubljana', 'sale', 'flat', 'Slovenia', 'Ljubljana', 187_000),
            ], 2),
            'S-EDIT' => $this->listing(Listing::factory()->approved(), $seller, [
                ...$this->lifetime(14),
                ...$this->keyed('S-EDIT', 'Corner Apartment in Maribor', 'sale', 'apartment', 'Slovenia', 'Maribor', 143_000),
            ]),
            'S-DEL' => $this->listing(Listing::factory()->approved(), $seller, [
                ...$this->lifetime(16),
                ...$this->keyed('S-DEL', 'Courtyard Condo in Zagreb', 'sale', 'condo', 'Croatia', 'Zagreb', 121_000),
            ]),
            'S-FRESH' => $this->listing(Listing::factory()->approved(), $seller, [
                ...$this->lifetime(30),
                ...$this->keyed('S-FRESH', 'Sea View Villa in Split', 'sale', 'villa', 'Croatia', 'Split', 641_000),
            ], 2),
            'S-EXPIRING' => $this->listing(Listing::factory()->approved(), $seller, [
                'approved_at' => now()->subDays(Listing::LIFETIME_DAYS - 5),
                'expires_at' => now()->addDays(5),
                ...$this->keyed('S-EXPIRING', 'Park Apartment in Vienna', 'sale', 'apartment', 'Austria', 'Vienna', 263_000),
            ]),
            'S-EXPIRED' => $this->listing(Listing::factory()->expired(), $seller, [
                'approved_at' => now()->subDays(Listing::LIFETIME_DAYS + 5),
                'expires_at' => now()->subDays(5),
                ...$this->keyed('S-EXPIRED', 'Garden Flat in Graz', 'sale', 'flat', 'Austria', 'Graz', 158_000),
            ]),
            'S-EXPIRED-B' => $this->listing(Listing::factory()->expired(), $seller, [
                'approved_at' => now()->subDays(Listing::LIFETIME_DAYS + 10),
                'expires_at' => now()->subDays(10),
                ...$this->keyed('S-EXPIRED-B', 'Hillside Villa in Munich', 'sale', 'villa', 'Germany', 'Munich', 733_000),
            ]),
            'S-SOLD' => $this->listing(Listing::factory()->sold(), $seller, [
                ...$this->lifetime(40),
                'closed_at' => now()->subDays(3),
                ...$this->keyed('S-SOLD', 'Loft Flat in Berlin', 'sale', 'flat', 'Germany', 'Berlin', 219_000),
            ]),
            'S-RENTED' => $this->listing(Listing::factory()->rented(), $seller, [
                ...$this->lifetime(45),
                'closed_at' => now()->subDays(5),
                ...$this->keyed('S-RENTED', 'Studio Apartment in Vienna', 'rent', 'apartment', 'Austria', 'Vienna', 1_130),
            ]),
            'S-ANCHOR' => $this->listing(Listing::factory()->approved(), $seller, [
                ...$this->lifetime(12),
                ...$this->keyed('S-ANCHOR', 'Family House in Ljubljana', 'sale', 'house', 'Slovenia', 'Ljubljana', 400_000),
                'bedrooms' => 4,
                'bathrooms' => 2,
                'area_value' => 186,
                'year_built' => 2009,
                'amenities' => [Amenity::Parking, Amenity::Garage, Amenity::Garden, Amenity::Terrace, Amenity::CentralHeating, Amenity::Storage],
            ], 6),
            'S-PEND-REJECT' => $this->listing(Listing::factory()->pendingReview(), $seller, [
                ...$this->keyed('S-PEND-REJECT', 'Building Plot in Novi Sad', 'sale', 'land', 'Serbia', 'Novi Sad', 61_000),
            ]),
            'S-PEND-DIGEST' => $this->listing(Listing::factory()->pendingReview(), $seller, [
                ...$this->keyed('S-PEND-DIGEST', 'Stone Cottage in Bled', 'sale', 'cottage', 'Slovenia', 'Bled', 287_000),
            ]),
            'S-PEND-PHONE-A' => $this->listing(Listing::factory()->pendingReview(), $seller, [
                ...$this->keyed('S-PEND-PHONE-A', 'Riverside Flat in Belgrade', 'rent', 'flat', 'Serbia', 'Belgrade', 690),
            ]),
            'S-PEND-PHONE-R' => $this->listing(Listing::factory()->pendingReview(), $seller, [
                ...$this->keyed('S-PEND-PHONE-R', 'Old Town Shopfront in Trieste', 'sale', 'commercial', 'Italy', 'Trieste', 352_000),
            ]),
            'S-REJ-2D' => $this->listing(Listing::factory()->rejected(), $seller, [
                ...$this->rejection(2),
                ...$this->keyed('S-REJ-2D', 'Canal Loft in Utrecht', 'sale', 'other', 'Netherlands', 'Utrecht', 99_000),
            ]),
            'S-REJ-8D' => $this->listing(Listing::factory()->rejected(), $seller, [
                ...$this->rejection(8),
                ...$this->keyed('S-REJ-8D', 'Tower Flat in Belgrade', 'sale', 'flat', 'Serbia', 'Belgrade', 88_000),
            ], 2),
            'S-REPORT-DOWN' => $this->listing(Listing::factory()->approved(), $seller, [
                ...$this->lifetime(20),
                ...$this->keyed('S-REPORT-DOWN', 'Market Condo in Berlin', 'sale', 'condo', 'Germany', 'Berlin', 277_000),
            ]),
            'S-REPORT-KEEP' => $this->listing(Listing::factory()->approved(), $seller, [
                ...$this->lifetime(22),
                ...$this->keyed('S-REPORT-KEEP', 'Harbour Apartment in Trieste', 'sale', 'apartment', 'Italy', 'Trieste', 205_000),
            ]),
        ];
    }

    /**
     * @return array<string, Listing>
     */
    private function seedAgencyListings(Agency $agency): array
    {
        return [
            'G-RENT' => $this->listing(Listing::factory()->approved(), $agency, [
                ...$this->lifetime(8),
                ...$this->keyed('G-RENT', 'Marina Flat in Split', 'rent', 'flat', 'Croatia', 'Split', 1_275),
            ]),
            'G-EXPIRING' => $this->listing(Listing::factory()->approved(), $agency, [
                'approved_at' => now()->subDays(Listing::LIFETIME_DAYS - 5)->addHours(3),
                'expires_at' => now()->addDays(5),
                ...$this->keyed('G-EXPIRING', 'Seafront Apartment in Piran', 'sale', 'apartment', 'Slovenia', 'Piran', 318_000),
            ]),
            'G-EDIT' => $this->listing(Listing::factory()->approved(), $agency, [
                ...$this->lifetime(18),
                ...$this->keyed('G-EDIT', 'Gallery Condo in Zagreb', 'sale', 'condo', 'Croatia', 'Zagreb', 233_000),
            ], 2),
            'G-DEL' => $this->listing(Listing::factory()->approved(), $agency, [
                ...$this->lifetime(19),
                ...$this->keyed('G-DEL', 'Building Plot in Graz', 'sale', 'land', 'Austria', 'Graz', 74_000),
            ]),
            'G-CHAT' => $this->listing(Listing::factory()->approved(), $agency, [
                ...$this->lifetime(9),
                ...$this->keyed('G-CHAT', 'Olive Grove Villa in Trieste', 'sale', 'villa', 'Italy', 'Trieste', 594_000),
            ]),
        ];
    }

    private function seedAnchorNeighbours(User $owner): void
    {
        $neighbours = [
            ['Garden House in Ljubljana', 'Slovenia', 'Ljubljana', 362_000],
            ['Linden House in Ljubljana', 'Slovenia', 'Ljubljana', 439_000],
            ['Hillside House in Maribor', 'Slovenia', 'Maribor', 331_000],
            ['Corner House in Graz', 'Austria', 'Graz', 481_000],
        ];

        foreach ($neighbours as [$title, $country, $city, $price]) {
            $this->filler($owner, [
                'title' => $title,
                'listing_type' => 'sale',
                'property_type' => 'house',
                'country' => $country,
                'city' => $city,
                'price' => $price,
            ]);
        }
    }

    private function seedConversations(User $buyer, User $seller, User $offender, User $leaver, Listing $fresh, Listing $buyerListing, Listing $leaverListing): void
    {
        $this->thread($fresh, $buyer, [
            [$buyer, 'Hello, is the villa still available?'],
            [$seller, 'Yes, it is. Would you like to visit?'],
            [$buyer, 'Could I come on Saturday morning?'],
            [$seller, 'Saturday at 10 works for me.'],
            [$seller, 'I will send you the exact address on Friday.'],
        ], 6, 2);

        $this->thread($buyerListing, $offender, [
            [$offender, 'Is the price negotiable?'],
            [$buyer, 'A little. What do you have in mind?'],
            [$offender, 'Half the price, or you are wasting my time, you clown.'],
        ], 30);

        $this->thread($leaverListing, $buyer, [
            [$buyer, 'Hi, is a parking space included?'],
            [$leaver, 'Yes, one space in the garage.'],
        ], 54);

        $gone = $this->account('gone@newbeginning.test', 'Gary Gone');
        $barn = $this->listing(Listing::factory()->approved(), $gone, [
            ...$this->lifetime(60),
            'title' => 'Old Barn in Graz',
            'listing_type' => 'sale',
            'property_type' => 'other',
            'country' => 'Austria',
            'city' => 'Graz',
            'price' => 77_000,
        ], 0);

        $this->thread($barn, $buyer, [
            [$buyer, 'Is the barn still for sale?'],
            [$gone, 'Yes, make me an offer.'],
        ], 78);

        $gone->delete();
    }

    private function seedMessageReport(User $owner, User $noisyBuyer, Listing $listing): void
    {
        $conversation = $this->thread($listing, $noisyBuyer, [
            [$noisyBuyer, 'Answer me right now.'],
            [$owner, 'Please be patient, I will reply this evening.'],
            [$noisyBuyer, 'Nobody would buy this dump anyway.'],
        ], 100);

        $message = $conversation->messages()->where('sender_id', $noisyBuyer->id)->reorder()->latest('id')->firstOrFail();

        $this->messageReports->report($message, $owner, 'Insults about my property.');
    }

    /**
     * @param  array<string, Listing>  $sellerListings
     */
    private function seedListingReports(User $admin, User $reporterOne, User $reporterTwo, User $reporterThree, array $sellerListings, Listing $resolvedListing): void
    {
        $this->listingReports->report($sellerListings['S-REPORT-DOWN'], $reporterOne, ['reason' => 'scam']);
        $this->listingReports->report($sellerListings['S-REPORT-DOWN'], $reporterTwo, ['reason' => 'incorrect_info']);
        $this->listingReports->report($sellerListings['S-REPORT-KEEP'], $reporterOne, ['reason' => 'duplicate']);
        $this->listingReports->report($sellerListings['S-EXPIRED-B'], $reporterTwo, ['reason' => 'no_longer_available']);

        $this->listingReports->report($resolvedListing, $reporterThree, ['reason' => 'other', 'details' => 'The photos look like a different building.']);
        $this->listingReports->dismiss($resolvedListing, $admin);
    }

    private function seedSavedSearches(User $buyer, User $collector): void
    {
        SavedSearch::factory()
            ->withFilters(['listingType' => 'sale', 'propertyType' => 'cottage', 'city' => 'Bled'])
            ->create(['user_id' => $buyer->id, 'name' => 'Cottages in Bled']);

        SavedSearch::factory()
            ->withFilters(['listingType' => 'rent', 'propertyType' => 'flat', 'city' => 'Vienna'])
            ->alertsDisabled()
            ->create(['user_id' => $buyer->id, 'name' => 'Flats to rent in Vienna']);

        foreach (array_slice(self::LOCATIONS, 0, 10) as [, $city]) {
            SavedSearch::factory()
                ->withFilters(['city' => $city])
                ->alertsDisabled()
                ->create(['user_id' => $collector->id, 'name' => "Homes in {$city}"]);
        }
    }

    private function seedContactMessages(User $admin, User $buyer): void
    {
        $messages = [
            [null, 'visitor.one@example.test', 'Question about agency fees', 'Do agencies pay to list their properties on the site?'],
            [$buyer, 'buyer@newbeginning.test', 'Viewing times', 'Can I ask an owner for a viewing outside the listed hours?'],
            [null, 'visitor.two@example.test', 'Listing photos', 'One listing shows the same photo twice.'],
            [null, 'visitor.three@example.test', 'Account help', 'I forgot which email address I registered with.'],
            [null, 'visitor.four@example.test', 'Thank you', 'I found my new flat through your site.'],
        ];

        foreach ($messages as $position => [$sender, $email, $subject, $body]) {
            $contactMessage = $this->contactMessages->create(['email' => $email, 'subject' => $subject, 'message' => $body], $sender);

            if ($position >= 3) {
                $this->contactMessages->markHandled($contactMessage, $admin);
            }
        }
    }

    private function account(string $email, string $name, UserRole $role = UserRole::RegularUser, bool $suspended = false): User
    {
        $factory = match ($role) {
            UserRole::Admin => User::factory()->admin(),
            UserRole::Agency => User::factory()->agency(),
            UserRole::RegularUser => User::factory()->regularUser(),
        };

        return ($suspended ? $factory->suspended() : $factory)->create([
            'name' => $name,
            'email' => $email,
        ]);
    }

    private function agency(string $email, string $ownerName, string $name, AgencyStatus $status, bool $suspended = false): Agency
    {
        $owner = $this->account($email, $ownerName, UserRole::Agency, $suspended);
        $position = $this->agencyIndex++;
        $slug = Str::slug($name);

        $factory = match ($status) {
            AgencyStatus::Approved => Agency::factory()->approved(),
            AgencyStatus::PendingVerification => Agency::factory()->pendingVerification(),
            AgencyStatus::Hidden => Agency::factory()->hidden(),
        };

        return $factory->create([
            'user_id' => $owner->id,
            'name' => $name,
            'legal_name' => "{$name} Ltd.",
            'slug' => $slug,
            'email' => "office@{$slug}.test",
            'phone' => sprintf('+386 1 555 %04d', 100 + $position),
            'website' => "https://{$slug}.test",
            'address' => sprintf('%d %s, %s', 2 + $position, self::STREETS[$position % count(self::STREETS)], self::LOCATIONS[$position % count(self::LOCATIONS)][1]),
            'description' => "{$name} sells and lets homes across Europe, with viewings arranged within the week.",
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function filler(User|Agency $owner, array $overrides = []): Listing
    {
        $index = $this->listingIndex;
        $approvedAt = now()->subHours(6 + $index * 7)->subMinutes(13);
        $photos = match (true) {
            $index % 9 === 8 => 3,
            $index % 4 === 3 => 2,
            default => 1,
        };

        return $this->listing(Listing::factory()->approved(), $owner, [
            'approved_at' => $approvedAt,
            'expires_at' => $approvedAt->copy()->addDays(Listing::LIFETIME_DAYS),
            ...$overrides,
        ], $photos);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function listing(ListingFactory $factory, User|Agency $owner, array $overrides = [], int $photos = 1): Listing
    {
        $attributes = [...$this->profile($this->listingIndex++), ...$overrides];
        $ownership = $owner instanceof Agency
            ? ['user_id' => null, 'agency_id' => $owner->id]
            : ['user_id' => $owner->id, 'agency_id' => null];

        $listing = $factory->create([
            ...$attributes,
            ...$ownership,
            'slug' => Str::slug($attributes['title']),
            'description' => $this->describe($attributes),
        ]);

        for ($photo = 0; $photo < $photos; $photo++) {
            $name = self::PHOTOS[$this->photoIndex++ % count(self::PHOTOS)];
            $this->images->upload($listing, new UploadedFile(public_path("images/{$name}"), $name, 'image/webp', null, true));
        }

        return $listing;
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(int $index): array
    {
        [$country, $city] = self::LOCATIONS[($index * 5) % count(self::LOCATIONS)];
        $propertyType = self::FILLER_TYPES[$index % count(self::FILLER_TYPES)];
        $listingType = $index % 3 === 2 ? 'rent' : 'sale';

        return [
            'title' => self::ADJECTIVES[$index % count(self::ADJECTIVES)].' '.self::TYPE_NOUNS[$propertyType].' in '.$city,
            'listing_type' => $listingType,
            'property_type' => $propertyType,
            'price' => $listingType === 'rent'
                ? 480 + (($index * 13) % 53) * 55
                : 95_000 + (($index * 23) % 97) * 9_500,
            'country' => $country,
            'city' => $city,
            'address_line' => (10 + $index).' '.self::STREETS[$index % count(self::STREETS)],
            'postal_code' => (string) (10_000 + $index * 101),
            'bedrooms' => $index % 5 + 1,
            'bathrooms' => $index % 3 + 1,
            'area_value' => 40 + ($index * 53) % 280,
            'year_built' => 1965 + ($index * 7) % 60,
            'amenities' => self::AMENITY_SETS[$index % count(self::AMENITY_SETS)],
            'contact_name' => null,
            'contact_email' => null,
            'contact_phone' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function describe(array $attributes): string
    {
        return sprintf(
            '%s: a %s of %d m² in %s, %s, with %d bedrooms and %d bathrooms, built in %d.',
            $attributes['title'],
            Str::lower(self::TYPE_NOUNS[$attributes['property_type']]),
            $attributes['area_value'],
            $attributes['city'],
            $attributes['country'],
            $attributes['bedrooms'],
            $attributes['bathrooms'],
            $attributes['year_built'],
        );
    }

    /**
     * @return array{title: string, listing_type: string, property_type: string, country: string, city: string, price: int}
     */
    private function keyed(string $key, string $title, string $listingType, string $propertyType, string $country, string $city, int $price): array
    {
        return [
            'title' => "{$key} {$title}",
            'listing_type' => $listingType,
            'property_type' => $propertyType,
            'country' => $country,
            'city' => $city,
            'price' => $price,
        ];
    }

    /**
     * @return array{approved_at: Carbon, expires_at: Carbon}
     */
    private function lifetime(int $approvedDaysAgo): array
    {
        $approvedAt = now()->subDays($approvedDaysAgo);

        return [
            'approved_at' => $approvedAt,
            'expires_at' => $approvedAt->copy()->addDays(Listing::LIFETIME_DAYS),
        ];
    }

    /**
     * @return array{rejected_at: Carbon, deleted_at: Carbon, rejection_reason: string}
     */
    private function rejection(int $daysAgo): array
    {
        return [
            'rejected_at' => now()->subDays($daysAgo),
            'deleted_at' => now()->subDays($daysAgo),
            'rejection_reason' => 'The photos do not show the property described.',
        ];
    }

    /**
     * @param  list<array{0: User, 1: string}>  $messages
     */
    private function thread(Listing $listing, User $buyer, array $messages, int $startedHoursAgo, int $unreadForBuyer = 0): Conversation
    {
        $conversation = $this->conversations->startOrGet($listing, $buyer);
        $startedAt = now()->subHours($startedHoursAgo);
        $sentAt = $startedAt->copy();
        $sentTimes = [];

        foreach ($messages as [$sender, $body]) {
            $sentAt = $sentAt->copy()->addMinutes(17);
            $message = new ChatMessage(['body' => $body]);
            $message->sender_id = $sender->id;
            $message->created_at = $sentAt;
            $message->updated_at = $sentAt;
            $conversation->messages()->save($message);
            $sentTimes[] = $sentAt;
        }

        Conversation::withoutTimestamps(fn () => $conversation->forceFill([
            'buyer_last_read_at' => $sentTimes[count($sentTimes) - 1 - $unreadForBuyer],
            'owner_last_read_at' => end($sentTimes),
            'created_at' => $startedAt,
            'updated_at' => end($sentTimes),
        ])->save());

        return $conversation;
    }
}
