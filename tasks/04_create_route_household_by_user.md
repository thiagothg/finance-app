# Create route apiResource household by user

List members of household and create/update a household name.

# requirements

- Use the household service to get the households by user id.
- endpoint to update/create a household name if doesn't exist yet.
- Only can edit the name of the user who created the household or have HouseholdMemberRole Member and owner.
- If user have the HouseholdMemberRole as Viwer can't edit the name.
- endpoint to list members of household
- endpoint to add/remove members of household
- Return a json response with the households.
- Use the HouseholdResource to format the response. 
- Count the number of households and return in the response.
- Return the user id of the user who created the household.
- Return each member of household and total spend of each member.

## fixes

- Now to add a user to a household the app will send (name, email, role)
    - all required
    - it will create a user and sent a email for invation 
    - the user can finish create the account and aceept the invation to household
    - the email sent will a link for the page to app
    - eedit route/controller and the rest addMember
- When finish account, should send another email for validation
    - should generate the numbers
    - send by email
    - and another endpoint to validate
    - store this number to validate after 

## Plan de fixes

1. **Database & Models**
    - Create a migration to add `validation_code` and `validation_code_expires_at` to the `users` table.
    - Update the `User` model to include these new fields and a `is_active` or `email_verified_at` check.
2. **Requests & Validation**
    - Update `AddHouseholdMemberRequest` to require `name`, `email`, and `role`.
    - Create `ValidateCodeRequest` for the new validation endpoint.
3. **Services**
    - Refactor `HouseholdService@addMember` to:
        - Create a new user if the email doesn't exist.
        - Generate an invitation link.
        - Trigger the invitation email.
    - Update `AuthService` to handle validation code generation and verification.
4. **Controllers & Routes**
    - Update `HouseholdController@addMember` to handle the new request data.
    - Add `POST /auth/validate` endpoint in `routes/api.php` (or relevant auth route file).
5. **Emails & Notifications**
    - Create `HouseholdInvitation` notification/mail.
    - Create `AccountValidationCode` notification/mail.


## fix2

- add to Househould (migration/model and factories) a code invitation
- when househould is created, generate this code too
- 8 random digits
- add a route to add a user to household
    - the code is required
- on migration make unique

## fix3

- is missing the status of invation of member
- return this status on model, on return of index of househoude and member list
- update tests
- only view 
- return the household with login user with its status and role