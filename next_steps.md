# Next Steps

Need to create refresh controller - all it needs to do is accept the refresh token.
we need to apply a hash to the refresh token. then use that value to find the user.
if we find a matching user then we can revoke any existing tokens and generate new ones.
save / issue to the user.
json reponse with the access token - cookie containing refresh token.


Create username and password form and flow. this will have to findCreate user generate tokens and then redirect the user.
