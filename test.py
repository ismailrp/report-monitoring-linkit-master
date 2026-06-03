import requests
import time

# Konfigurasi API
LOGIN_URL = "https://be-mobile.viatelecom.id/api/mobile/user/login/email"
MAX_ATTEMPTS = 10000
RETRY_DELAY = 2  # detik

def login_user(email, password):
    """Fungsi untuk login user"""
    login_data = {
        "email": email,
        "password": password
    }

    headers = {
        "Content-Type": "application/json",
        "User-Agent": "Mozilla/5.0"
    }

    attempt = 0
    while attempt < MAX_ATTEMPTS:
        attempt += 1
        print(f"\n[{time.strftime('%H:%M:%S')}] Attempt {attempt} untuk {email}")

        try:
            response = requests.post(
                LOGIN_URL,
                json=login_data,
                headers=headers,
                timeout=10
            )

            print(f"Status Code: {response.status_code}")

            if response.status_code == 200:
                result = response.json()
                token = result.get("access_token")
                if token:
                    print(f"✅ Login berhasil!")
                    print(f"Token: {token[:50]}...")
                    return {
                        "success": True,
                        "token": token,
                        "user_data": result.get("user", {})
                    }
                else:
                    print("❌ Token tidak ditemukan dalam response")

            elif response.status_code == 401:
                print("❌ Email atau password salah")
                break  # Stop retry untuk kredensial salah

            elif response.status_code == 429:
                print("⚠️  Terlalu banyak request, tunggu...")
                time.sleep(5)
                continue

            else:
                print(f"❌ Gagal dengan status: {response.status_code}")
                print(f"Response: {response.text}")

        except requests.exceptions.Timeout:
            print("⏱️  Request timeout")
        except requests.exceptions.ConnectionError:
            print("🔌 Koneksi error")
        except Exception as e:
            print(f"⚠️  Error: {e}")

        # Delay sebelum retry
        if attempt < MAX_ATTEMPTS:
            print(f"🔄 Retry dalam {RETRY_DELAY} detik...")
            time.sleep(RETRY_DELAY)

    return {"success": False, "error": "Max attempts reached"}

# Contoh penggunaan
if __name__ == "__main__":
    EMAIL = "user@example.com"
    PASSWORD = "password123"

    result = login_user(EMAIL, PASSWORD)
    print(f"\nFinal result: {result['success']}")
