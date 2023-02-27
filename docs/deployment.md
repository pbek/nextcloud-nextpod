# Deploying to the app stores

## Nextcloud

### Prerequisites

- Copy your app certificate files to `./docker/nextcloud/certificates`

### Signing and releasing

- Make sure the version in `appinfo/info.xml` and the `CHANGELOG.md` are updated
- Sign the app with `cd docker && make sign-app`
    - You should now have a `nextpod-nc.tar.gz` in your git directory
    - Check the content of the archive for unwanted files (you can exclude more files in
      `docker/nextcloud/sign-app.sh`)
- Commit and push your changes to GitHub
- Create a new release on [NextPod releases](https://github.com/pbek/nextcloud-nextpod/releases)
  with the version like `22.5.0` as *Tag name* and *Release title* and the changelog text of the current
  release as *Release notes*
    - You also need to upload `nextpod-nc.tar.gz` to the release and get its url
      like `https://github.com/pbek/nextcloud-nextpod/releases/download/1.0.0/nextpod-nc.tar.gz`
- Take the text from *Signature for your app archive*, which was printed by the sign-app command and
  release the app at [Upload app release](https://apps.nextcloud.com/developer/apps/releases/new)
    - You need the download link to `nextpod-nc.tar.gz` from the GitHub release
- The new version should then appear on the [NextPod store page](https://apps.nextcloud.com/apps/nextpod)

<!--
## ownCloud

### Prerequisites

- Copy your app certificate files to `./docker/owncloud/certificates`

### Signing and releasing

- Make sure the version in `appinfo/info.xml` and the `CHANGELOG.md` are updated
- Sign the app with `cd docker && make sign-app-owncloud`
    - You should now have a `nextpod-oc.tar.gz` in your git directory
    - Check the content of the archive for unwanted files (you can exclude more files in
      `docker/owncloud/sign-app.sh`)
- Upload `nextpod-oc.tar.gz` on [ownCloud producs](https://marketplace.owncloud.com/account/products)
- Publish app on [nextpod](https://marketplace.owncloud.com/account/edit/nextpod)!
-->