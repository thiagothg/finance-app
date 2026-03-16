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

