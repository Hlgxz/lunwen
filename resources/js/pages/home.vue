<template>
  <div class="row">
    <div class="col-lg-10 m-auto">
      <card :title="$t('home')">
        <div>
          <p>用户名：{{ user.name }}</p>
          <p>邮箱：{{ user.email }}</p>
          <p>角色：{{ user.role.display_name }}</p>
        </div>
      </card>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
export default {
  middleware: 'auth',
  async asyncData () {
    const { data } = await axios.get('/api/user')
    return {
      user: data
    }
  },
  data () {
    return {
      user: {}
    }
  },
  async created () {
    try {
      const { data } = await axios.get('/api/user')
      this.user = data
    } catch (error) {
      console.error('获取用户数据失败:', error)
    }
  },

  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
