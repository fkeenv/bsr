export type PropertyHouseholdMember = {
    name: string;
};

export type PropertyEmergencyContact = {
    name: string;
    contact_number: string;
    relationship: string;
};

export type PropertyVehicle = {
    year: number;
    make: string;
    model: string;
    plate: string;
    sticker_number: string;
};

export type PropertyProfile = {
    property_id: number;
    property_label: string;
    saved_at: string | null;
    household_members: PropertyHouseholdMember[];
    emergency_contacts: PropertyEmergencyContact[];
    vehicles: PropertyVehicle[];
};

export type PropertyProfileForm = {
    household_members: PropertyHouseholdMember[];
    emergency_contacts: PropertyEmergencyContact[];
    vehicles: (Omit<PropertyVehicle, 'year'> & { year: string })[];
};
