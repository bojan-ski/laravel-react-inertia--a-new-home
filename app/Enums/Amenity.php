<?php

namespace App\Enums;

enum Amenity: string
{
    case Parking = 'parking';
    case Garage = 'garage';
    case Elevator = 'elevator';
    case Balcony = 'balcony';
    case Terrace = 'terrace';
    case Garden = 'garden';
    case Pool = 'pool';
    case AirConditioning = 'air_conditioning';
    case CentralHeating = 'central_heating';
    case Furnished = 'furnished';
    case PetsAllowed = 'pets_allowed';
    case Storage = 'storage';
    case Fireplace = 'fireplace';
    case Accessible = 'accessible';
    case Security = 'security';
}
