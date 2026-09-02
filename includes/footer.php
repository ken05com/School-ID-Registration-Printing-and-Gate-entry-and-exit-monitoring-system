    </div><!-- /.content -->
  </main>
</div>

<!-- Camera capture modal (shared — hidden by default) -->
<div id="cameraModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:1.5rem;max-width:420px;width:90%;text-align:center;">
    <div style="font-weight:700;font-size:1.1rem;margin-bottom:1rem;">Take Photo</div>
    <video id="cameraVideo" autoplay playsinline style="width:100%;border-radius:10px;background:#2d2023;max-height:320px;"></video>
    <canvas id="captureCanvas" style="display:none;"></canvas>
    <div style="margin-top:1rem;display:flex;gap:.7rem;justify-content:center;">
      <button id="captureConfirm" class="btn btn-primary" type="button">Capture</button>
      <button id="captureCancel" class="btn btn-ghost" type="button">Cancel</button>
    </div>
  </div>
</div>

<!-- Edit student modal (hidden by default) -->
<div id="editModal" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.7);align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:14px;padding:1.5rem;max-width:480px;width:92%;">
    <div style="font-weight:700;font-size:1.1rem;margin-bottom:1rem;">Edit Student</div>
    <form id="editStudentForm" method="post" class="form-grid">
      <input type="hidden" name="action" value="edit_student">
      <input type="hidden" name="id" value="">
      <div class="field" style="grid-column:1/-1"><label>Full Name *</label><input name="full_name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email"></div>
      <div class="field"><label>Mobile No.</label><input name="phone"></div>
      <div class="field" style="grid-column:1/-1"><label>Course *</label><input name="course" required></div>
      <div class="field"><label>Year Level</label>
        <select name="year_level">
          <option value="">— Select —</option>
          <option>1st Year</option><option>2nd Year</option><option>3rd Year</option><option>4th Year</option><option>5th Year</option>
        </select>
      </div>
      <div class="field"><label>Section</label><input name="section"></div>
      <div class="field" style="grid-column:1/-1"><label>Address</label><input name="address"></div>
      <div style="grid-column:1/-1;display:flex;gap:.7rem;justify-content:flex-end;">
        <button type="button" id="editCancel" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/app.js"></script>
</body>
</html>
