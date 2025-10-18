from flask import Flask, request, send_file, make_response
from flask_cors import CORS
import qrcode
from io import BytesIO

app = Flask(__name__)
CORS(app)  # Bật CORS

@app.route('/generate_qr', methods=['GET'])
def generate_qr():
    data = request.args.get('data')
    if not data:
        return "Thiếu dữ liệu để tạo QR!", 400

    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_L,
        box_size=10,
        border=4,
    )
    qr.add_data(data)
    qr.make(fit=True)

    img = qr.make_image(fill="black", back_color="white")
    img_io = BytesIO()
    img.save(img_io, 'PNG')
    img_io.seek(0)

    # Tạo phản hồi và thêm header CORP
    response = make_response(send_file(img_io, mimetype='image/png'))
    response.headers['Cross-Origin-Resource-Policy'] = 'cross-origin'  # Cho phép nhúng từ mọi nguồn
    return response

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
