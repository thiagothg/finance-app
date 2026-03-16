# Create route for categories

Endpoint for manage categories

# Requirements

- List all categories
- On list only categories of the household
- Separate categories by type (income, expense) in CategoryType enum
- Only who is the (owner/member) of the household can manage it or the ower of the category.
- On create/update name must be unique in the household and type.
- On delete category, check if there is any transaction with this category, if yes, return error.
- On update category, check if there is any transaction with this category, if yes, return error.
- Name, type, icon, color are required.

# Updates

- Include a column budget for category.
- Include on Get category the budget.
- budget is optional (create/update).
- add on migration and model this new column.

## Autenticação

- Route protected.
- Only the (owner/member) of the category can manage it or the ower of the household.

## Resposta

- Return a json response with the categories.
- Use the CategoryResource to format the response. 
- Count the number of categories and return in the response.
- Return the user id of the user who created the category.
- Return each category and total spend of each category.

## Endpoints

- GET /api/categories - List all categories
    - Query params: type (income, expense)
    - Return all categories of the household
    - Count the number of categories and return in the response.
    - Return each category and total spend of each category.
- POST /api/categories - Create a new category
    - Request body: name, type, icon, color
    - Return the created category
- GET /api/categories/{id} - Get a category
    - Return the category
- PUT /api/categories/{id} - Update a category
    - Request body: name, type, icon, color
    - Return the updated category
- DELETE /api/categories/{id} - Delete a category
    - Return the deleted category