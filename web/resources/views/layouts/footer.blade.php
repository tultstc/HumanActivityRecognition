<footer class="footer px-4 text-[12px]">
    <div>Copyright &copy; 2024 <a href="#">STC R&D CENTER.</a>
        All right reserved</div>
    <div class="font-bold ms-auto">
        <span>{{ Illuminate\Support\Facades\Redis::get('system_status') == 1 ? 'Activated -' : 'No License -' }}</span>
    </div>
    <div>&nbsp;<b>Version</b> 1.0.0</div>
</footer>
