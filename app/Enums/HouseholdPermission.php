<?php

namespace App\Enums;

enum HouseholdPermission: string
{
    case PropertiesRead = 'properties.read';
    case PropertiesCreate = 'properties.create';
    case PropertiesEdit = 'properties.edit';
    case PropertiesDelete = 'properties.delete';
    case PropertiesDeactivate = 'properties.deactivate';
    case PropertiesReactivate = 'properties.reactivate';
    case PropertiesImport = 'properties.import';
    case PropertiesImportSample = 'properties.import-sample';
    case ProfilesRead = 'properties.profiles.read';
    case ProfilesEdit = 'properties.profiles.edit';
    case InvitationsRead = 'invitations.read';
    case InvitationsCreate = 'invitations.create';
    case InvitationsRevoke = 'invitations.revoke';
    case InvitationsCredentials = 'invitations.credentials';
    case MembershipsRead = 'memberships.read';
    case MembershipsChangeRole = 'memberships.change-role';
    case MembershipsEnd = 'memberships.end';
    case SuspendsRead = 'suspends.read';
    case SuspendsCreate = 'suspends.create';
    case SuspendsEdit = 'suspends.edit';

    public function feature(): string
    {
        return match ($this) {
            self::PropertiesRead => 'Properties',
            self::PropertiesCreate => 'Properties',
            self::PropertiesEdit => 'Properties',
            self::PropertiesDelete => 'Properties',
            self::PropertiesDeactivate => 'Properties',
            self::PropertiesReactivate => 'Properties',
            self::PropertiesImport => 'Properties',
            self::PropertiesImportSample => 'Properties',
            self::ProfilesRead => 'Properties',
            self::ProfilesEdit => 'Properties',
            self::InvitationsRead => 'Property Invitations',
            self::InvitationsCreate => 'Property Invitations',
            self::InvitationsRevoke => 'Property Invitations',
            self::InvitationsCredentials => 'Property Invitations',
            self::MembershipsRead => 'Memberships',
            self::MembershipsChangeRole => 'Memberships',
            self::MembershipsEnd => 'Memberships',
            self::SuspendsRead => 'Suspends',
            self::SuspendsCreate => 'Suspends',
            self::SuspendsEdit => 'Suspends',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PropertiesRead => 'List Properties',
            self::PropertiesCreate => 'Create Properties',
            self::PropertiesEdit => 'Edit Properties',
            self::PropertiesDelete => 'Delete uncharged Properties',
            self::PropertiesDeactivate => 'Deactivate Properties',
            self::PropertiesReactivate => 'Reactivate Properties',
            self::PropertiesImport => 'Import Properties',
            self::PropertiesImportSample => 'Download import sample',
            self::ProfilesRead => 'Read Property Profiles',
            self::ProfilesEdit => 'Edit Property Profiles',
            self::InvitationsRead => 'List Invitations',
            self::InvitationsCreate => 'Create Invitations',
            self::InvitationsRevoke => 'Revoke Invitations',
            self::InvitationsCredentials => 'Retrieve Invitation credentials',
            self::MembershipsRead => 'List Memberships',
            self::MembershipsChangeRole => 'Change Membership roles',
            self::MembershipsEnd => 'End Memberships',
            self::SuspendsRead => 'List Suspends',
            self::SuspendsCreate => 'Create Suspends',
            self::SuspendsEdit => 'Edit Suspends',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PropertiesRead => 'Read the association Property roster.',
            self::PropertiesCreate => 'Add a Property to the roster.',
            self::PropertiesEdit => 'Update Property details, with existing Opening Balance rules.',
            self::PropertiesDelete => 'Delete a Property only when its history allows deletion.',
            self::PropertiesDeactivate => 'Remove Properties from future levies while preserving history.',
            self::PropertiesReactivate => 'Return inactive Properties to the levy set.',
            self::PropertiesImport => 'Import the roster from CSV.',
            self::PropertiesImportSample => 'Open import instructions and download the sample CSV.',
            self::ProfilesRead => 'View household details across the association.',
            self::ProfilesEdit => 'Update household details across the association.',
            self::InvitationsRead => 'Read the Invitation roster without revealing credentials.',
            self::InvitationsCreate => 'Issue Owner or Resident Invitations for active Properties.',
            self::InvitationsRevoke => 'Disable unused Invitations.',
            self::InvitationsCredentials => 'Copy links and codes for unused Invitations.',
            self::MembershipsRead => 'Read live Memberships across the association.',
            self::MembershipsChangeRole => 'Change Owner or Resident roles, subject to lifecycle and self-management rules.',
            self::MembershipsEnd => 'End another account’s Membership with a reason.',
            self::SuspendsRead => 'Read Fee Type pauses across Properties.',
            self::SuspendsCreate => 'Pause a Fee Type for a Property and Billing Periods.',
            self::SuspendsEdit => 'Change a pause while preserving Billing Period constraints.',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
