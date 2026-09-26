const SDK_URL = 'https://connect.facebook.net/en_US/sdk.js';

/**
 * WhatsApp Business App coexistence (Embedded Signup).
 * Meta requires sessionInfoVersion "3" with featureType (see onboarding-business-app-users).
 * Do not add api_access_only here.
 */
export const COEXISTENCE_EMBEDDED_SIGNUP_EXTRAS = {
    setup: {},
    featureType: 'whatsapp_business_app_onboarding',
    sessionInfoVersion: '3',
};

let sdkPromise = null;

export function loadFacebookSdk(appId, graphVersion) {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('Browser only'));
    }

    if (window.FB) {
        return Promise.resolve(window.FB);
    }

    if (sdkPromise) {
        return sdkPromise;
    }

    sdkPromise = new Promise((resolve, reject) => {
        window.fbAsyncInit = function fbAsyncInit() {
            window.FB.init({
                appId,
                cookie: true,
                xfbml: false,
                version: graphVersion,
            });
            resolve(window.FB);
        };

        const existing = document.getElementById('facebook-jssdk');
        if (existing) {
            return;
        }

        const script = document.createElement('script');
        script.id = 'facebook-jssdk';
        script.async = true;
        script.defer = true;
        script.src = SDK_URL;
        script.onerror = () => reject(new Error('Failed to load Meta SDK'));
        document.body.appendChild(script);
    });

    return sdkPromise;
}

/**
 * @returns {Promise<{ code: string, embeddedEvent: object|null }>}
 */
export function launchCoexistenceEmbeddedSignup({ appId, graphVersion, configId }) {
    return new Promise((resolve, reject) => {
        let embeddedEvent = null;

        const onMessage = (event) => {
            if (typeof event.origin !== 'string' || !event.origin.includes('facebook.com')) {
                return;
            }

            let payload = event.data;
            if (typeof payload === 'string') {
                try {
                    payload = JSON.parse(payload);
                } catch {
                    return;
                }
            }

            if (!payload || payload.type !== 'WA_EMBEDDED_SIGNUP') {
                return;
            }

            embeddedEvent = payload;
        };

        window.addEventListener('message', onMessage);

        loadFacebookSdk(appId, graphVersion)
            .then((FB) => {
                FB.login(
                    (response) => {
                        window.removeEventListener('message', onMessage);

                        if (!response?.authResponse?.code) {
                            reject(new Error('Meta signup was cancelled or did not return an authorization code.'));

                            return;
                        }

                        resolve({
                            code: response.authResponse.code,
                            embeddedEvent,
                        });
                    },
                    {
                        config_id: configId,
                        response_type: 'code',
                        override_default_response_type: true,
                        extras: COEXISTENCE_EMBEDDED_SIGNUP_EXTRAS,
                    },
                );
            })
            .catch((error) => {
                window.removeEventListener('message', onMessage);
                reject(error);
            });
    });
}
