// Minimal C header for PHP FFI — declares only what reqxide needs from libcurl-impersonate.

// Standard curl types
typedef void CURL;
typedef int CURLcode;
typedef int CURLoption;
typedef int CURLINFO;

// curl_slist for header lists
struct curl_slist {
    char *data;
    struct curl_slist *next;
};

// C stdio (for temp file response capture)
typedef void FILE;
FILE *fopen(const char *path, const char *mode);
int fclose(FILE *stream);

// Standard curl functions
CURL *curl_easy_init(void);
void curl_easy_cleanup(CURL *curl);
CURLcode curl_easy_setopt(CURL *curl, CURLoption option, ...);
CURLcode curl_easy_perform(CURL *curl);
CURLcode curl_easy_getinfo(CURL *curl, CURLINFO info, ...);
const char *curl_easy_strerror(CURLcode code);
void curl_easy_reset(CURL *curl);

// Header list functions
struct curl_slist *curl_slist_append(struct curl_slist *list, const char *string);
void curl_slist_free_all(struct curl_slist *list);

// Standard CURLOPT constants
#define CURLOPT_URL 10002
#define CURLOPT_HTTPHEADER 10023
#define CURLOPT_CUSTOMREQUEST 10036
#define CURLOPT_POSTFIELDS 10015
#define CURLOPT_POSTFIELDSIZE 60
#define CURLOPT_WRITEFUNCTION 20011
#define CURLOPT_WRITEDATA 10001
#define CURLOPT_HEADERFUNCTION 20079
#define CURLOPT_HEADERDATA 10029
#define CURLOPT_TIMEOUT_MS 155
#define CURLOPT_CONNECTTIMEOUT_MS 156
#define CURLOPT_SSL_VERIFYPEER 64
#define CURLOPT_SSL_VERIFYHOST 81
#define CURLOPT_PROXY 10004
#define CURLOPT_PROXYTYPE 101
#define CURLOPT_PROXYUSERPWD 10006
#define CURLOPT_FOLLOWLOCATION 52
#define CURLOPT_MAXREDIRS 68
#define CURLOPT_HTTP_VERSION 84
#define CURLOPT_SSLVERSION 32
#define CURLOPT_SSL_CIPHER_LIST 10083
#define CURLOPT_CAINFO 10065
#define CURLOPT_RETURNTRANSFER 19913
#define CURLOPT_HTTPGET 80
#define CURLOPT_POST 47
#define CURLOPT_NOBODY 44

// curl_impersonate-specific options (these are what make FFI worth it)
#define CURLOPT_SSL_EC_CURVES 10306
#define CURLOPT_SSL_SIG_HASH_ALGS 10307
#define CURLOPT_SSL_ENABLE_ALPS 311
#define CURLOPT_SSL_CERT_COMPRESSION 10312
#define CURLOPT_SSL_ENABLE_TICKET 313
#define CURLOPT_TLS_GREASE 314
#define CURLOPT_TLS_PERMUTE_EXTENSIONS 315
#define CURLOPT_SSL_ECH 10316
#define CURLOPT_TLS_KEY_SHARES 10317
#define CURLOPT_HTTP2_PSEUDO_HEADERS_ORDER 10318
#define CURLOPT_HTTP2_SETTINGS 10319
#define CURLOPT_HTTP2_WINDOW_UPDATE 320
#define CURLOPT_HTTP2_STREAMS 10321
#define CURLOPT_SSL_SIG_ALGS 10307

// CURLINFO constants
#define CURLINFO_RESPONSE_CODE 2097154

// CURLcode values
#define CURLE_OK 0

// HTTP version constants
#define CURL_HTTP_VERSION_2_0 3

// Proxy type constants
#define CURLPROXY_HTTP 0
#define CURLPROXY_HTTPS 2
#define CURLPROXY_SOCKS4 4
#define CURLPROXY_SOCKS5 7

// SSL version constants
#define CURL_SSLVERSION_DEFAULT 0
#define CURL_SSLVERSION_TLSv1_0 4
#define CURL_SSLVERSION_TLSv1_1 5
#define CURL_SSLVERSION_TLSv1_2 6
#define CURL_SSLVERSION_TLSv1_3 7
#define CURL_SSLVERSION_MAX_DEFAULT 65536
#define CURL_SSLVERSION_MAX_TLSv1_0 262144
#define CURL_SSLVERSION_MAX_TLSv1_1 327680
#define CURL_SSLVERSION_MAX_TLSv1_2 393216
#define CURL_SSLVERSION_MAX_TLSv1_3 458752
