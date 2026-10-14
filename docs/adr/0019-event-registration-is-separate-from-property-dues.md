# Event registration owns admission and stays separate from Property dues

Event registrations belong to a User Account and Event, not a Property. Event registration owns
reserved attendee places and the rules for locking price/audience and retaining registration
history; Event management uses that boundary while holding the Event row lock. Registration
writers must use the same Event lock so capacity and management changes cannot race.

Event payments and refunds will be their own records. They never use Property Payment allocations,
Opening Balance, Charges, Prepaid, or Outstanding Balance: admission is a separate purchase and an
account can register without a Property Membership. The first publishing slice includes the small
registration-record foundation needed to enforce the first-registration and reserved-place guards;
registration submission, attendees, tickets, and payments arrive in later slices.

Events are cancelled rather than deleted through the management UI. Registered Events also have a
restrictive foreign key to preserve their history. Published start/end times stay locked until the
rescheduling slice can record schedule history and notify existing attendees.
