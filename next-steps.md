# Next Steps for SAML + Keycloak Integration

This document outlines the recommended approach for maintaining a Symfony application integrated with Keycloak via SAML.

## 1. Dynamic Metadata Fetching

* Create a service to fetch the Keycloak SAML metadata from `/protocol/saml/descriptor`.
* Parse the XML to extract certificates and endpoints.
* Cache the metadata to avoid frequent HTTP requests, using `validUntil` or a TTL.
* Ensure fallback to the last known good metadata in case Keycloak is unavailable.

## 2. Event-Driven Updates

* Register an event listener or subscriber on SAML routes (`/saml/acs`, `/saml/logout`).
* On each SAML request, ensure the SP configuration uses the latest metadata.
* This avoids manual updates to certificates and keeps SP and IdP in sync.

## 3. SP Configuration

* Keep static configuration for:

    * `entityId`
    * ACS URL (`assertionConsumerService`)
    * SLO URL (`singleLogoutService`)
* Avoid hardcoding IdP certificates in `settings.php`; rely on metadata service instead.
* Use strict validation in production (`strict = true`, `wantAssertionsSigned = true`).

## 4. Caching and Reliability

* Store fetched metadata in a cache (memory, filesystem, or Redis).
* Use the cached copy if Keycloak is temporarily unavailable.
* Refresh metadata periodically or on each request depending on performance vs. security needs.

## 5. User and Client Management

* Continue defining users in the realm JSON export.
* Assign roles and permissions as needed.
* Ensure `clientId` matches SP `entityId` if required for SAML flow.

## 6. Monitoring and Logging

* Enable debug logs during development (`debug = true`).
* Monitor SAML errors and certificate mismatches in production.
* Log metadata fetches and updates for traceability.

## Summary

* Automate metadata fetching and certificate management.
* Use Symfony events to inject dynamic configuration before SAML request handling.
* Avoid manual certificate updates in `settings.php`.
* Keep strict validation in production.
* Cache metadata to balance reliability and performance.
