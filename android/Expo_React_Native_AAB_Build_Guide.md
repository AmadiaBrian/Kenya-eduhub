# React Native (Expo) → Android AAB Build Guide

## Prerequisites

-   Node.js installed
-   Expo project (`app.json`, `package.json`)
-   Expo account
-   Internet connection

## 1. Open your project

``` bash
cd C:\path\to\your\project
```

Verify:

``` bash
dir
```

Look for:

-   app.json
-   package.json
-   app/
-   assets/

## 2. Install EAS CLI

``` bash
npm install -g eas-cli
```

Verify:

``` bash
eas --version
```

## 3. Log in

``` bash
eas login
```

Check:

``` bash
eas whoami
```

## 4. Configure EAS

``` bash
eas build:configure
```

If asked to create an EAS project, answer **Y**.

Choose **Android** when asked which platform to configure.

This creates `eas.json`.

## 5. Check app.json

Ensure `android.package` exists.

Example:

``` json
"android": {
  "package": "com.amadia.kenyaeduhub"
}
```

Choose a unique package name before publishing.

## 6. Build the Android App Bundle

``` bash
eas build -p android
```

If asked:

    Generate a new Android Keystore?

Answer **Y**.

## 7. Wait for the build

Expo uploads your project and builds it in the cloud.

Typical messages:

-   Uploading project
-   Queued
-   Building
-   Finished

## 8. Download the AAB

Open the build URL shown by EAS.

When the status is **Finished**, download the `.aab` file.

## 9. Upload to Google Play

Sign in to Google Play Console.

Create your app (or open an existing one).

Go to **Production** (or another release track), create a release,
upload the `.aab`, complete the store listing, and submit for review.

## Common commands

``` bash
eas --version
eas whoami
eas build:configure
eas build -p android
```

## Common issues

### "eas: command not found"

``` bash
npm install -g eas-cli
```

### "Not logged in"

``` bash
eas login
```

### Missing android.package

Add it to `app.json`.

### Build failed

Run:

``` bash
npx expo doctor
```

Fix reported issues and build again.

## Notes

-   Keep the same package name after publishing.
-   Let Expo manage your Android keystore unless you have a reason not
    to.
-   Save your project in Git before making major changes.
